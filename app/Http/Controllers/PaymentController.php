<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use App\Models\Cart;

class PaymentController extends Controller
{
    public function createPaymentIntent(Request $request)
    {
        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
        $cart = Cart::where('user_id', auth()->id())
            ->with('items.product')
            ->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json([
                'error' => true,
                'message' => 'El carrito está vacío o no existe.'
            ], 400);
        }

        $subtotal = 0;

        foreach ($cart->items as $item) {
            $product = $item->product;
            if (!$product || $product->status === 'ARCHIVED') {
                continue;
            }
            $price = ($product->discount_price !== null && $product->discount_price > 0)
                ? $product->discount_price
                : $product->price;

            $subtotal += $price * $item->quantity;
        }

        if ($subtotal <= 0) {
            return response()->json([
                'error' => true,
                'message' => 'No hay productos activos o válidos en el carrito para procesar el pago.'
            ], 400);
        }

        $tax = round($subtotal * 0.13, 2);
        $shipping = 1500;
        $total = $subtotal + $tax + $shipping;

        $amount = intval(round($total * 100));

        $paymentIntent = \Stripe\PaymentIntent::create([
            'amount' => $amount,
            'currency' => 'crc',
            'metadata' => [
                'user_id' => auth()->id(),
                'cart_id' => $cart->id,
            ],
        ]);

        return response()->json([
            'clientSecret' => $paymentIntent->client_secret,
            'amount' => round($total, 2),
            'currency' => 'CRC',
        ]);
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
