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

        // 1️⃣ Validar datos
        $validated = $request->validate([
            'street' => 'nullable|string|max:150',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'payment_method' => 'required|string|in:VISA,MASTERCARD,AMEX,PAYPAL',
            'currency' => 'nullable|string|in:CRC,USD',

            // 💳 Datos de tarjeta (mock)
            'card.name' => 'required|string|max:100',
            'card.number' => 'required|string|size:16',
            'card.exp_month' => 'required|string|size:2',
            'card.exp_year' => 'required|string|size:4',
            'card.cvv' => 'required|string|size:3',
        ]);

        $currency = $validated['currency'] ?? 'CRC';

        // 2️⃣ Cargar carrito con productos
        $cart = Cart::where('user_id', $user->id)
            ->with(['items.product:id,store_id,name,price,discount_price,stock,image_1_url'])
            ->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['error' => true, 'step' => 'CART_EMPTY', 'message' => 'El carrito está vacío'], 400);
        }

        // 3️⃣ Calcular totales
        $taxRate = (float) (config('app.tax_rate', env('APP_TAX_RATE', 0.13)));
        $shipping = (int) (config('app.shipping_flat', env('APP_SHIPPING_FLAT', 0)));
        $subtotal = 0;

        foreach ($cart->items as $item) {
            $product = $item->product;

            if (!$product) {
                return response()->json(['error' => true, 'step' => 'TOTALS', 'message' => "Producto con ID {$item->product_id} no existe"], 400);
            }

            $unitPrice = ($product->discount_price && $product->discount_price > 0)
                ? $product->discount_price
                : $product->price;

            if ($unitPrice <= 0) {
                return response()->json(['error' => true, 'step' => 'TOTALS', 'message' => "El producto {$product->name} tiene un precio inválido"], 400);
            }

            $subtotal += (int) $unitPrice * (int) $item->quantity;
        }

        $taxes = (int) round($subtotal * $taxRate);
        $total = (int) ($subtotal + $taxes + $shipping);

        // 4️⃣ Simular pago
        $cardData = $request->input('card', []);
        $charge = $payments->charge($total, $currency, $cardData);

        if (!($charge['approved'] ?? false)) {
            return response()->json([
                'error' => true,
                'step' => 'PAYMENT',
                'message' => 'Pago rechazado',
                'details' => $charge,
            ], 402);
        }

        // 5️⃣ Crear orden e ítems
        $order = null;

        try {
            DB::beginTransaction();

            // 🧾 Crear orden principal
            $order = Order::create([
                'user_id' => $user->id,
                'status' => 'PAID',
                'subtotal' => $subtotal,
                'shipping' => $shipping,
                'taxes' => $taxes,
                'total' => $total,
                'payment_method' => $validated['payment_method'],
                'payment_id' => $charge['transaction_id'] ?? null,
                'street' => $validated['street'] ?? null,
                'city' => $validated['city'] ?? null,
                'state' => $validated['state'] ?? null,
                'zip_code' => $validated['zip_code'] ?? null,
                'country' => $validated['country'] ?? null,
            ]);

            // 🧩 Crear los items
            foreach ($cart->items as $idx => $item) {
                $product = $item->product;
                $unitPrice = $product->discount_price ?? $product->price;

                if ($product->stock !== null && $product->stock < $item->quantity) {
                    DB::rollBack();
                    return response()->json([
                        'error' => true,
                        'step' => 'E2_STOCK',
                        'message' => "Stock insuficiente para {$product->name}",
                        'product_id' => $product->id,
                        'stock' => $product->stock,
                        'requested_qty' => $item->quantity,
                    ], 422);
                }

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

            // 💰 Registrar transacción
            Transaction::create([
                'user_id' => $user->id,
                'order_id' => $order->id,
                'type' => 'INCOME',
                'amount' => $total,
                'currency' => $currency,
                'description' => 'Pago aprobado vía ' . $validated['payment_method'],
            ]);

            // 🧹 Limpiar carrito
            $cart->items()->delete();

            DB::commit();

            return response()->json([
                'message' => 'Orden creada y pago aprobado ✅',
                'order' => $order->load(['items.product:id,name,image_1_url,price,discount_price']),
                'payment_result' => $charge,
            ], 201);

        } catch (QueryException $qe) {
            DB::rollBack();
            if ($order && $order->exists) $order->delete();

            return response()->json([
                'error' => true,
                'step' => 'E_QUERY',
                'message' => 'Error de base de datos al procesar el pedido',
                'sql_state' => $qe->errorInfo[0] ?? null,
                'sql_code' => $qe->errorInfo[1] ?? null,
                'sql_detail' => $qe->errorInfo[2] ?? null,
            ], 500);

        } catch (\Throwable $e) {
            DB::rollBack();
            if ($order && $order->exists) $order->delete();

            return response()->json([
                'error' => true,
                'step' => 'E_UNCAUGHT',
                'message' => 'Error no controlado en checkout',
                'exception' => get_class($e),
                'detail' => $e->getMessage(),
            ], 500);
        }
    }
}
