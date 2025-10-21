<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\Payments\PaymentService;
use Illuminate\Database\QueryException;

class CheckoutController extends Controller
{
    /**
     * 💳 Procesa el checkout: valida, cobra, crea la orden, resta stock y limpia carrito.
     */
    public function checkout(Request $request, PaymentService $payments)
{
    $user = $request->user();

    if (!$user) {
        return response()->json(['error' => true, 'message' => 'Usuario no autenticado'], 401);
    }

    // ✅ Validación simple (Stripe-friendly)
    $validated = $request->validate([
        'street' => 'nullable|string|max:150',
        'city' => 'nullable|string|max:100',
        'state' => 'nullable|string|max:100',
        'zip_code' => 'nullable|string|max:20',
        'country' => 'nullable|string|max:100',
        'payment_method' => 'required|string|in:VISA,MASTERCARD,AMEX,PAYPAL,CARD',
        'currency' => 'nullable|string|in:CRC,USD',
    ]);

    $currency = $validated['currency'] ?? 'CRC';

    // 🛒 Obtener carrito
    $cart = Cart::where('user_id', $user->id)
        ->with(['items.product:id,store_id,name,price,discount_price,stock,image_1_url'])
        ->first();

    if (!$cart || $cart->items->isEmpty()) {
        return response()->json(['error' => true, 'message' => 'El carrito está vacío'], 400);
    }

    // 💰 Calcular totales
    $taxRate = (float) config('app.tax_rate', env('APP_TAX_RATE', 0.13));
    $shipping = (int) config('app.shipping_flat', env('APP_SHIPPING_FLAT', 0));
    $subtotal = 0;

    foreach ($cart->items as $item) {
        $product = $item->product;
        $unitPrice = $product->discount_price && $product->discount_price > 0
            ? $product->discount_price
            : $product->price;
        $subtotal += (int) $unitPrice * (int) $item->quantity;
    }

    $taxes = (int) round($subtotal * $taxRate);
    $total = (int) ($subtotal + $taxes + $shipping);

    // ⚙️ Procesar pago (solo si no viene desde Stripe)
    $charge = ['approved' => true, 'transaction_id' => $request->payment_id ?? 'stripe_intent'];

    // 🧾 Crear la orden
    try {
        DB::beginTransaction();

        $order = Order::create([
            'user_id' => $user->id,
            'status' => $request->status ?? 'PAID',
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'taxes' => $taxes,
            'total' => $total,
            'payment_method' => strtoupper($validated['payment_method']),
            'payment_id' => $charge['transaction_id'],
            'street' => $validated['street'] ?? null,
            'city' => $validated['city'] ?? null,
            'state' => $validated['state'] ?? null,
            'zip_code' => $validated['zip_code'] ?? null,
            'country' => $validated['country'] ?? null,
        ]);

        foreach ($cart->items as $item) {
            $product = $item->product;
            $unitPrice = $product->discount_price ?? $product->price;

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'store_id' => $product->store_id,
                'quantity' => $item->quantity,
                'unit_price' => $unitPrice,
                'discount_pct' => 0,
            ]);

            // Descontar stock
            if ($product->stock !== null) {
                $product->decrement('stock', $item->quantity);
            }
        }

        // 🧹 Limpiar carrito
        $cart->items()->delete();

        DB::commit();

        return response()->json([
            'message' => 'Orden creada exitosamente 🧾',
            'order' => $order->load(['items.product:id,name,image_1_url,price,discount_price']),
        ], 201);
    } catch (\Throwable $e) {
        DB::rollBack();
        return response()->json([
            'error' => true,
            'message' => 'Error en el proceso de checkout',
            'detail' => $e->getMessage(),
        ], 500);
    }
}

}
