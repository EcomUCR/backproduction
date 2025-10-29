<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\StoreReview;
use App\Models\User;
use App\Models\Notification;
use App\Models\Review;
use Illuminate\Http\Request;
use App\Services\BrevoMailer;
use Illuminate\Support\Facades\Log;

class StoreReviewNotificationController extends Controller
{
    // Send notification and email when a store receives a new review.
    public function sendReviewNotification(Request $request)
    {
        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'review_id' => 'required|exists:reviews,id',
        ]);

        $store = Store::with('user')->findOrFail($validated['store_id']);
        $review = StoreReview::with('user')->findOrFail($validated['review_id']);
        $seller = $store->user;

        $subject = 'Has recibido una nueva reseña en tu tienda | TukiShop';
        $to = $seller->email;

        $body = view('emails.store-new-review', [
            'store_name' => $store->name,
            'reviewer_name' => $review->user->first_name . ' ' . $review->user->last_name,
            'reviewer_image' => $review->user->image ?? null,
            'rating' => $review->rating,
            'comment' => $review->comment,
            'date' => $review->created_at->format('d/m/Y'),
            'store_dashboard_url' => url("/seller/dashboard/reviews/{$store->id}")
        ])->render();

        try {
            BrevoMailer::send($to, $subject, $body);
        } catch (\Throwable $th) {
            Log::error('Error al enviar correo de reseña: ' . $th->getMessage());
        }

        Notification::create([
            'user_id' => $seller->id,
            'role' => 'SELLER',
            'type' => 'REVIEW',
            'title' => 'Nueva reseña en tu tienda',
            'message' => "{$review->user->first_name} dejó una nueva reseña en tu tienda «{$store->name}».",
            'related_id' => $review->id,
            'related_type' => 'review',
            'priority' => 'NORMAL',
            'data' => [
                'rating' => $review->rating,
                'comment' => $review->comment,
                'date' => $review->created_at->toDateTimeString(),
            ],
        ]);

        return response()->json([
            'message' => 'Notificación y correo enviados correctamente',
            'store' => $store->name,
            'review' => $review->id,
        ], 201);
    }
}
