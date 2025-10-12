<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\Payments\PaymentService;

class CheckoutController extends Controller
{
    public function checkout(Request $request, PaymentService $payments)
    {
        $user = $request->user();

        $validated = $request->validate([
            // Dirección mínima (ajusta a tu modelo Address si lo tienes)
            'street'         => 'nullable|string|max:150',
            'city'           => 'nullable|string|max:100',
            'state'          => 'nullable|string|max:100',
            'zip_code'       => 'nullable|string|max:20',
            'country'        => 'nullable|string|max:100',

            // Info de pago (mock – solo validamos forma)
            'payment_method' => 'required|string|in:VISA,MASTERCARD,AMEX,PAYPAL',
            'currency'       => 'nullable|string|in:CRC,USD',   // default CRC
            // Si quieres simular captura de tarjeta:
            'card.name'      => 'nullable|string|max:100',
            'card.number'    => 'nullable|string|max:30',
            'card.exp_month' => 'nullable|string|max:2',
            'card.exp_year'  => 'nullable|string|max:4',
            'card.cvv'       => 'nullable|string|max:4',
        ]);

        $currency = $validated['currency'] ?? 'CRC';

        // 1) Cargar carrito
        $cart = Cart::where('user_id', $user->id)
            ->with(['items.product:id,store_id,name,price,discount_price,stock'])
            ->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['error' => 'El carrito está vacío'], 400);
        }

        // 2) Calcular totales (puedes meter impuestos/envío desde ENV)
        $taxRate   = (float) (config('app.tax_rate', env('APP_TAX_RATE', 0))); // 0.13, etc.
        $shipping  = (int) (config('app.shipping_flat', env('APP_SHIPPING_FLAT', 0))); // fijo en colones
        $subtotal  = 0;

        foreach ($cart->items as $item) {
            /** @var \App\Models\Product $product */
            $product   = $item->product;
            $unitPrice = $product->discount_price ?? $product->price;
            $subtotal += (int) $unitPrice * (int) $item->quantity;
        }

        $taxes = (int) round($subtotal * $taxRate);
        $total = (int) ($subtotal + $taxes + $shipping);

        // 3) Intentar pago (mock)
        $cardData = $request->input('card', []);
        $charge   = $payments->charge($total, $currency, $cardData);

        if (!$charge['approved']) {
            return response()->json([
                'error'   => 'Pago rechazado',
                'details' => $charge,
            ], 402);
        }

        // 4) Crear Order / OrderItems en transacción
        $order = DB::transaction(function () use ($user, $validated, $subtotal, $shipping, $taxes, $total, $cart, $charge) {
            // Status: PAID si ya aprobó el pago
            $order = Order::create([
                'user_id'        => $user->id,
                'status'         => 'PAID',
                'subtotal'       => $subtotal,
                'shipping'       => $shipping,
                'taxes'          => $taxes,
                'total'          => $total,
                'payment_method' => $validated['payment_method'] ?? 'VISA',

                // Dirección (si no usas address_id)
                'street'   => $validated['street']   ?? null,
                'city'     => $validated['city']     ?? null,
                'state'    => $validated['state']    ?? null,
                'zip_code' => $validated['zip_code'] ?? null,
                'country'  => $validated['country']  ?? null,

                // Puedes guardar metadatos del pago si tu tabla Orders tiene campos JSON
                // 'payment_meta' => json_encode($charge), // solo si existe
            ]);

            // Crear items + descontar stock
            foreach ($cart->items as $item) {
                $product   = $item->product;
                $unitPrice = $product->discount_price ?? $product->price;

                // Chequeo de stock simple
                if ($product->stock !== null && $product->stock < $item->quantity) {
                    throw new \RuntimeException("Stock insuficiente para {$product->name}");
                }

                OrderItem::create([
                    'order_id'    => $order->id,
                    'product_id'  => $product->id,
                    'store_id'    => $product->store_id,
                    'quantity'    => $item->quantity,
                    'unit_price'  => $unitPrice,
                    'discount_pct'=> 0,
                ]);

                // Descontar stock (si tu modelo lo maneja)
                if ($product->stock !== null) {
                    $product->decrement('stock', $item->quantity);
                }
            }

            // Limpiar carrito
            $cart->items()->delete();

            return $order->load(['items.product:id,name,image_1_url,price,discount_price']);
        });

        return response()->json([
            'message'          => 'Orden creada y pago aprobado',
            'order'            => $order,
            'payment_result'   => $charge,
        ], 201);
    }
}
