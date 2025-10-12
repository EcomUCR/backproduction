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
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    public function checkout(Request $request, PaymentService $payments)
    {
        $user = $request->user();

        // 🧾 VALIDACIÓN COMPLETA
        $validated = $request->validate([
            // Dirección
            'street' => 'nullable|string|max:150',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',

            // Método de pago
            'payment_method' => 'required|string|in:VISA,MASTERCARD,AMEX,PAYPAL',
            'currency' => 'nullable|string|in:CRC,USD',

            // Datos de tarjeta (mock o real)
            'card.name' => 'required|string|max:100',
            'card.number' => 'required|string|size:16',
            'card.exp_month' => 'required|string|size:2',
            'card.exp_year' => 'required|string|size:4',
            'card.cvv' => 'required|string|size:3',
        ]);

        $currency = $validated['currency'] ?? 'CRC';

        // 🛒 1) CARGAR CARRITO
        $cart = Cart::where('user_id', $user->id)
            ->with(['items.product:id,store_id,name,price,discount_price,stock'])
            ->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['error' => 'El carrito está vacío'], 400);
        }

        // 🧮 2) CALCULAR TOTALES
        $taxRate = (float) (config('app.tax_rate', env('APP_TAX_RATE', 0.13)));
        $shipping = (int) (config('app.shipping_flat', env('APP_SHIPPING_FLAT', 0)));
        $subtotal = 0;

        foreach ($cart->items as $item) {
            /** @var Product $product */
            $product = $item->product;

            if (!$product) {
                return response()->json(['error' => "Producto con ID {$item->product_id} no existe"], 400);
            }

            $unitPrice = $product->discount_price ?? $product->price;

            if ($unitPrice <= 0) {
                return response()->json(['error' => "El producto {$product->name} tiene un precio inválido"], 400);
            }

            $subtotal += (int) $unitPrice * (int) $item->quantity;
        }

        $taxes = (int) round($subtotal * $taxRate);
        $total = (int) ($subtotal + $taxes + $shipping);

        // 💳 3) INTENTAR PAGO (mock)
        $cardData = $request->input('card', []);
        $charge = $payments->charge($total, $currency, $cardData);

        if (!$charge['approved']) {
            return response()->json([
                'error' => 'Pago rechazado',
                'details' => $charge,
            ], 402);
        }

        // 🧱 4) TRANSACCIÓN PRINCIPAL
        try {
            Log::info('🧩 CHECKOUT INIT', [
                'user_id' => $user->id,
                'email' => $user->email ?? 'N/A',
                'subtotal' => $subtotal,
                'shipping' => $shipping,
                'taxes' => $taxes,
                'total' => $total,
                'payment_method' => $validated['payment_method'] ?? null,
            ]);

            $order = DB::transaction(function () use ($user, $validated, $subtotal, $shipping, $taxes, $total, $cart, $charge, $currency) {
                $order = Order::create([
                    'user_id' => $user->id,
                    'status' => 'PAID', // o 'PENDING' si prefieres el flujo normal
                    'subtotal' => $subtotal ?? 0,
                    'shipping' => $shipping ?? 0,
                    'taxes' => $taxes ?? 0,
                    'total' => $total ?? 0,
                    'payment_method' => $validated['payment_method'] ?? 'VISA',
                    'street' => $validated['street'] ?? null,
                    'city' => $validated['city'] ?? null,
                    'state' => $validated['state'] ?? null,
                    'zip_code' => $validated['zip_code'] ?? null,
                    'country' => $validated['country'] ?? null,
                ]);

                // Crear los ítems
                foreach ($cart->items as $item) {
                    $product = $item->product;
                    $unitPrice = $product->discount_price ?? $product->price;

                    if ($product->stock !== null && $product->stock < $item->quantity) {
                        throw new \RuntimeException("Stock insuficiente para {$product->name}");
                    }

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'store_id' => $product->store_id,
                        'quantity' => $item->quantity,
                        'unit_price' => $unitPrice,
                        'discount_pct' => 0,
                    ]);

                    if ($product->stock !== null) {
                        $product->decrement('stock', $item->quantity);
                    }
                }

                // Crear transacción
                Transaction::create([
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                    'type' => 'INCOME',
                    'amount' => $total,
                    'currency' => $currency,
                    'description' => 'Pago aprobado vía ' . ($validated['payment_method'] ?? 'VISA'),
                ]);

                $cart->items()->delete();

                return $order->load(['items.product:id,name,image_1_url,price,discount_price']);
            });
        } catch (\Throwable $e) {
            return response()->json([
                'error' => true,
                'message' => 'Error al procesar la compra',
                'exception' => get_class($e),
                'exception_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => collect(explode("\n", $e->getTraceAsString()))->take(10)->toArray(), // primeros 10 niveles
            ], 500);
        }

        // ✅ RESPUESTA FINAL
        return response()->json([
            'message' => 'Orden creada y pago aprobado',
            'order' => $order,
            'payment_result' => $charge,
        ], 201);
    }
}
