<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    // List all stores.
    public function index()
    {
        return response()->json(Store::all());
    }

    // Show store associated with a specific user.
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

    // Show a specific store by ID.
    public function show($id)
    {
        $store = Store::with(['user', 'storeSocials', 'banners', 'products', 'reviews'])->findOrFail($id);
        return response()->json($store);
    }

    // Create a new store.
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

        // 🆕 rating no se incluye manualmente
        $validatedData['rating'] = 0.0;

        $store = Store::create($validatedData);
        return response()->json($store, 201);
    }

    // Update an existing store
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
            'social_links' => 'nullable|array',
            'social_links.*.type' => 'required_with:social_links|string|max:50',
            'social_links.*.text' => 'required_with:social_links|string|max:255',
        ]);

        $data = $validatedData;

        // Mantener imágenes si se pasan
        if ($request->has('image')) $data['image'] = $request->input('image');
        if ($request->has('banner')) $data['banner'] = $request->input('banner');

        // 🧠 rating no se modifica desde request
        unset($data['rating']);

        $store->update($data);

        // 🔄 Actualización social_links (sin cambios)
        if ($request->filled('social_links')) {
            $links = $request->input('social_links');
            if (is_string($links)) {
                $decoded = json_decode($links, true);
                if (json_last_error() === JSON_ERROR_NONE) $links = $decoded;
            }

            if (is_array($links)) {
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

        // 🔄 Reload de relaciones
        $store->load(['user', 'storeSocials', 'banners', 'products', 'reviews']);

        // 🔔 Notificación si fue verificada
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
                        'dashboard_url' => env('DASHBOARD_URL', 'https://tukishop.vercel.app/profile'),
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

    // ✅ Actualizar tienda desde el panel de administración
    public function adminUpdate(Request $request, $id)
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
            'social_links' => 'nullable|array',
            'social_links.*.type' => 'required_with:social_links|string|max:50',
            'social_links.*.text' => 'required_with:social_links|string|max:255',
        ]);

        // 🧠 rating nunca se actualiza desde admin manualmente
        unset($validatedData['rating']);

        $store->update($validatedData);

        // Resto del código sin cambios ↓↓↓
        if ($request->filled('social_links')) {
            $links = $request->input('social_links');
            if (is_string($links)) {
                $decoded = json_decode($links, true);
                if (json_last_error() === JSON_ERROR_NONE) $links = $decoded;
            }

            if (is_array($links)) {
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

        $store->load(['user', 'storeSocials', 'banners', 'products', 'reviews']);
        $isNowVerified = (bool) $store->is_verified;

        try {
            $user = $store->user;

            if ($user) {
                if (!$wasVerified && $isNowVerified) {
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

                    $subject = '🎉 ¡Tu tienda ha sido verificada!';
                    $body = view('emails.store-verified-html', [
                        'store_name' => $store->name,
                        'owner_name' => trim($user->first_name . ' ' . $user->last_name) ?: $user->username,
                        'verification_date' => now()->format('d/m/Y H:i'),
                        'dashboard_url' => env('DASHBOARD_URL', 'https://tukishopcr.com/dashboard/store'),
                    ])->render();

                    \App\Services\BrevoMailer::send($user->email, $subject, $body);
                } else {
                    \App\Models\Notification::create([
                        'user_id' => $user->id,
                        'role' => $user->role,
                        'type' => 'STORE_UPDATED_BY_ADMIN',
                        'title' => '⚙️ Tu tienda fue actualizada por un administrador',
                        'message' => "Un administrador ha realizado cambios en tu tienda '{$store->name}'. 
                                      Si no reconoces esta acción, contáctanos para más información.",
                        'related_id' => $store->id,
                        'related_type' => 'store',
                        'priority' => 'NORMAL',
                        'is_read' => false,
                        'data' => [
                            'store_id' => $store->id,
                            'store_name' => $store->name,
                            'updated_by' => 'ADMIN',
                        ],
                    ]);

                    $subject = '⚙️ Tu tienda ha sido actualizada por un administrador';
                    $body = view('emails.store-updated-by-admin-html', [
                        'store_name' => $store->name,
                        'owner_name' => trim($user->first_name . ' ' . $user->last_name) ?: $user->username,
                        'dashboard_url' => env('DASHBOARD_URL', 'https://tukishop.vercel.app/profile'),
                    ])->render();

                    \App\Services\BrevoMailer::send($user->email, $subject, $body);
                }
            }
        } catch (\Exception $e) {
            \Log::error('❌ Error al enviar correo/notificación de actualización de tienda por admin: ' . $e->getMessage());
        }

        return response()->json([
            'store' => $store,
            'message' => 'Tienda actualizada correctamente por el administrador',
        ]);
    }


    /**
     * Obtener solo el rating de una tienda específica.
     */
    public function getRating($id)
    {
        $store = Store::select('id', 'name', 'rating')
            ->where('id', $id)
            ->first();

        if (!$store) {
            return response()->json(['message' => 'Tienda no encontrada'], 404);
        }

        return response()->json([
            'store_id' => $store->id,
            'store_name' => $store->name,
            'rating' => (float) $store->rating,
        ]);
    }

    // Delete a store.
    public function destroy($id)
    {
        $store = Store::findOrFail($id);
        $store->delete();

        return response()->json(null, 204);
    }
}
