<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function index()
    {
        return response()->json(Store::all());
    }

    public function showByUser($user_id)
    {
        $store = Store::with(['user', 'storeSocials', 'banners', 'products', 'reviews'])
            ->where('user_id', $user_id)
            ->first();

        if (!$store) {
            return response()->json(['message' => 'Tienda no encontrada para este usuario'], 404);
        }

        return response()->json(['store' => $store]);
    }

    public function show($id)
    {
        $store = Store::with(['user', 'storeSocials', 'banners', 'products', 'reviews'])->findOrFail($id);
        return response()->json($store);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
            'name' => 'required|string|max:80',
            'slug' => 'required|string|max:100|unique:stores',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:store_categories,id',
            'business_name' => 'nullable|string|max:150',
            'tax_id' => 'nullable|string|max:50',
            'legal_type' => 'nullable|string|max:30',
            'registered_address' => 'nullable|string',
            'address' => 'nullable|string',
            'support_email' => 'nullable|string|email|max:120',
            'support_phone' => 'nullable|string|max:30',
            'status' => 'nullable|string|in:ACTIVE,SUSPENDED,CLOSED',
        ]);

        $store = Store::create($validatedData);
        return response()->json($store, 201);
    }

    public function update(Request $request, $id)
    {
        $store = Store::findOrFail($id);
        $wasVerified = (bool) $store->is_verified;

        $validatedData = $request->validate([
            'user_id' => 'sometimes|exists:users,id',
            'name' => 'sometimes|string|max:80',
            'slug' => 'sometimes|string|max:100|unique:stores,slug,' . $store->id,
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:store_categories,id',
            'business_name' => 'nullable|string|max:150',
            'tax_id' => 'nullable|string|max:50',
            'legal_type' => 'nullable|string|max:30',
            'registered_address' => 'nullable|string',
            'address' => 'nullable|string',
            'support_email' => 'nullable|string|email|max:120',
            'support_phone' => 'nullable|string|max:30',
            'image' => 'nullable|string|max:1024',
            'banner' => 'nullable|string|max:1024',
            'is_verified' => 'nullable|boolean',
            'status' => 'nullable|string|in:ACTIVE,SUSPENDED,CLOSED',

            // 🔹 Redes sociales
            'social_links' => 'nullable|array',
            'social_links.*.type' => 'required_with:social_links|string|max:50',
            'social_links.*.text' => 'required_with:social_links|string|max:255',
        ]);

        // 🔹 Actualizar datos básicos
        $data = $validatedData;

        // 🔹 Agregar imágenes si vienen en payload
        // 🔹 Agregar imágenes si vienen en payload
        if ($request->has('image')) {
            $data['image'] = $request->input('image');
        }
        if ($request->has('banner')) {
            $data['banner'] = $request->input('banner');
        }

        $store->update($data);

        // 🔹 Actualizar redes sociales
        // 🔹 Actualizar redes sociales
        if ($request->filled('social_links')) {
            $links = $request->input('social_links');

            // Si viene como string JSON, decodificarlo
            if (is_string($links)) {
                $decoded = json_decode($links, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $links = $decoded;
                }
            }

            if (is_array($links)) {
                // 🔸 Limpiar previas y recrear todas correctamente
                $store->storeSocials()->delete();

                foreach ($links as $link) {
                    if (!empty($link['type']) && !empty($link['text'])) {
                        $store->storeSocials()->create([
                            'platform' => $link['type'],
                            'url' => $link['text'],
                        ]);
                    }
                }
            }
        }


        // 🔹 Recargar con relaciones
        $store->load(['user', 'storeSocials', 'banners', 'products', 'reviews']);

        // 🔹 Si se verificó por primera vez, enviar notificación/correo
        $isNowVerified = (bool) $store->is_verified;
        if (!$wasVerified && $isNowVerified) {
            try {
                $user = $store->user;
                if ($user) {
                    \App\Models\Notification::create([
                        'user_id' => $user->id,
                        'role' => $user->role,
                        'type' => 'STORE_VERIFIED',
                        'title' => '🎉 ¡Tu tienda ha sido verificada!',
                        'message' => "La tienda '{$store->name}' fue revisada y ahora está verificada oficialmente en TukiShop.",
                        'related_id' => $store->id,
                        'related_type' => 'store',
                        'priority' => 'NORMAL',
                        'is_read' => false,
                        'data' => [
                            'store_id' => $store->id,
                            'store_name' => $store->name,
                        ],
                    ]);

                    $subject = '¡Tu tienda ha sido verificada!';
                    $body = view('emails.store-verified-html', [
                        'store_name' => $store->name,
                        'owner_name' => trim($user->first_name . ' ' . $user->last_name) ?: $user->username,
                        'verification_date' => now()->format('d/m/Y H:i'),
                        'dashboard_url' => env('DASHBOARD_URL', 'https://tukishopcr.com/dashboard/store'),
                    ])->render();

                    \App\Services\BrevoMailer::send($user->email, $subject, $body);
                }
            } catch (\Exception $e) {
                \Log::error('❌ Error al enviar notificación/correo de verificación: ' . $e->getMessage());
            }
        }

        return response()->json([
            'store' => $store,
            'message' => 'Tienda actualizada correctamente',
        ]);
    }



    public function destroy($id)
    {
        $store = Store::findOrFail($id);
        $store->delete();

        return response()->json(null, 204);
    }
}
