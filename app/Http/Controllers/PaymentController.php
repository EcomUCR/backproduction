<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class PaymentController extends Controller
{
    public function createPaymentIntent(Request $request)
{
    \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

    // ✅ Recalcular el total desde el carrito del usuario autenticado
    $cart = Cart::where('user_id', auth()->id())->with('items.product')->first();

    $subtotal = $cart->items->sum(fn($item) => $item->product->price * $item->quantity);
    $tax = $subtotal * 0.13;
    $shipping = 1500;
    $total = $subtotal + $tax + $shipping;

    $amount = intval(round($total * 100)); // en céntimos

    $paymentIntent = \Stripe\PaymentIntent::create([
        'amount' => $amount,
        'currency' => 'crc',
        'metadata' => [
            'user_id' => auth()->id(),
            'cart_id' => $cart->id,
        ],
    ]);

    return response()->json(['clientSecret' => $paymentIntent->client_secret]);
}

    public function webhook(Request $request)
    {
        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? null;
        $secret = env('STRIPE_WEBHOOK_SECRET');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $secret);
        } catch (\UnexpectedValueException $e) {
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        if ($event->type === 'payment_intent.succeeded') {
            $paymentIntent = $event->data->object;
            Log::info('✅ Pago exitoso: ' . $paymentIntent->id);
        }

        if ($event->type === 'payment_intent.payment_failed') {
            $paymentIntent = $event->data->object;
            Log::warning('❌ Pago fallido: ' . $paymentIntent->id);
        }

        return response()->json(['status' => 'success']);
    }
}
