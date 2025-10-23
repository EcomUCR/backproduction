<?php

namespace App\Http\Controllers;

use App\Models\StoreReview;
use App\Models\Store;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Services\BrevoMailer;
use Illuminate\Support\Facades\Log;

class StoreReviewController extends Controller
{
    public function index()
    {
        $storeReviews = StoreReview::all();
        return response()->json($storeReviews);
    }

    public function show($id)
    {
        $storeReview = StoreReview::findOrFail($id);
        return response()->json($storeReview);
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'store_id' => 'required|exists:stores,id',
                'user_id' => 'required|exists:users,id',
                'rating' => 'required|integer|min:1|max:5',
                'comment' => 'nullable|string',
                'like' => 'nullable|boolean',
                'dislike' => 'nullable|boolean',
            ]);

            // ✅ Crear reseña
            $storeReview = StoreReview::create($validatedData);

            // 🔎 Obtener datos relacionados
            $store = Store::with('user')->findOrFail($validatedData['store_id']);
            $seller = $store->user;
            $reviewer = User::findOrFail($validatedData['user_id']);

            // 📩 Datos del correo
            $subject = 'Has recibido una nueva reseña en tu tienda | TukiShop';
            $to = $seller->email;

            $body = view('emails.store-new-review', [
                'store_name' => $store->name,
                'reviewer_name' => trim(($reviewer->first_name ?? '') . ' ' . ($reviewer->last_name ?? '')) ?: $reviewer->username,
                'reviewer_image' => $reviewer->image ?? null,
                'rating' => $storeReview->rating,
                'comment' => $storeReview->comment ?? '(Sin comentario)',
                'date' => $storeReview->created_at->format('d/m/Y'),
                'store_dashboard_url' => url("/seller/dashboard/reviews/{$store->id}")
            ])->render();

            try {
                // ✉️ Enviar correo al dueño de la tienda
                BrevoMailer::send($to, $subject, $body);
            } catch (\Throwable $th) {
                // El envío del correo falla, pero no detiene el flujo
                Log::warning('⚠️ Error al enviar correo de reseña: ' . $th->getMessage());
            }

            // 🔔 Crear notificaciones
            Notification::create([
                'user_id' => $seller->id,
                'role' => 'SELLER',
                'type' => 'REVIEW',
                'title' => 'Nueva reseña en tu tienda',
                'message' => "{$reviewer->first_name} dejó una nueva reseña en tu tienda «{$store->name}».",
                'related_id' => $storeReview->id,
                'related_type' => 'store_review',
                'priority' => 'NORMAL',
                'data' => json_encode([
                    'rating' => $storeReview->rating,
                    'comment' => $storeReview->comment,
                    'date' => $storeReview->created_at->toDateTimeString(),
                ]),
            ]);

            Notification::create([
                'user_id' => $seller->id,
                'role' => 'STORE',
                'type' => 'STORE_REVIEW',
                'title' => 'Tu tienda ha recibido una nueva reseña',
                'message' => "La tienda «{$store->name}» ha recibido una nueva reseña de {$reviewer->first_name}.",
                'related_id' => $store->id,
                'related_type' => 'store',
                'priority' => 'NORMAL',
                'data' => json_encode([
                    'store_name' => $store->name,
                    'reviewer' => ($reviewer->first_name ?? '') . ' ' . ($reviewer->last_name ?? ''),
                    'rating' => $storeReview->rating,
                    'comment' => $storeReview->comment,
                    'review_id' => $storeReview->id,
                ]),
            ]);

            return response()->json([
                'message' => 'Reseña creada correctamente',
                'review' => $storeReview
            ], 201);

        } catch (\Throwable $th) {
            // 🔍 Captura y muestra el error real como JSON
            return response()->json([
                'error' => true,
                'message' => $th->getMessage(),
                'trace' => $th->getFile() . ':' . $th->getLine()
            ], 500);
        }
    }


    public function update(Request $request, $id)
    {
        $storeReview = StoreReview::findOrFail($id);

        $validatedData = $request->validate([
            'rating' => 'sometimes|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        $storeReview->update($validatedData);

        return response()->json($storeReview);
    }

    public function destroy($id)
    {
        $storeReview = StoreReview::findOrFail($id);
        $storeReview->delete();

        return response()->json(null, 204);
    }

    public function summary($store_id)
    {
        $reviews = StoreReview::where('store_id', $store_id)->get();
        $average = round($reviews->avg('rating'), 1);
        $distribution = $reviews->groupBy('rating')->map->count();

        return response()->json([
            'average' => $average,
            'distribution' => $distribution,
            'total' => $reviews->count(),
        ]);
    }

    public function reviewsByStore($store_id)
    {
        $reviews = StoreReview::where('store_id', $store_id)
            ->with(['user:id,first_name,last_name,username,image'])
            ->latest()
            ->get();

        return response()->json($reviews);
    }
}
