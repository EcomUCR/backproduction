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
    public function checkout(Request $request, PaymentService $payments)
    {
        $user = $request->user();

        // 1) Validación
        $validated = $request->validate([
            'street'         => 'nullable|string|max:150',
            'city'           => 'nullable|string|max:100',
            'state'          => 'nullable|string|max:100',
            'zip_code'       => 'nullable|string|max:20',
            'country'        => 'nullable|string|max:100',
            'payment_method' => 'required|string|in:VISA,MASTERCARD,AMEX,PAYPAL',
            'currency'       => 'nullable|string|in:CRC,USD',

            // Datos de tarjeta (mock)
            'card.name'      => 'required|string|max:100',
            'card.number'    => 'required|string|size:16',
            'card.exp_month' => 'required|string|size:2',
            'card.exp_year'  => 'required|string|size:4',
            'card.cvv'       => 'required|string|size:3',
        ]);

        $currency = $validated['currency'] ?? 'CRC';

        // 2) Cargar carrito
        $cart = Cart::where('user_id', $user->id)
            ->with(['items.product:id,store_id,name,price,discount_price,stock'])
            ->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['error' => true, 'step' => 'CART_EMPTY', 'message' => 'El carrito está vacío'], 400);
        }

        // 3) Calcular totales
        $taxRate  = (float) (config('app.tax_rate', env('APP_TAX_RATE', 0.13)));
        $shipping = (int) (config('app.shipping_flat', env('APP_SHIPPING_FLAT', 0)));
        $subtotal = 0;

        foreach ($cart->items as $item) {
            /** @var Product|null $product */
            $product = $item->product;
            if (!$product) {
                return response()->json(['error' => true, 'step' => 'TOTALS', 'message' => "Producto con ID {$item->product_id} no existe"], 400);
            }
            $unitPrice = $product->discount_price ?? $product->price;
            if ($unitPrice <= 0) {
                return response()->json(['error' => true, 'step' => 'TOTALS', 'message' => "El producto {$product->name} tiene un precio inválido"], 400);
            }
            $subtotal += (int) $unitPrice * (int) $item->quantity;
        }

        $taxes = (int) round($subtotal * $taxRate);
        $total = (int) ($subtotal + $taxes + $shipping);

        // 4) Mock de pago
        $cardData = $request->input('card', []);
        $charge = $payments->charge($total, $currency, $cardData);
        if (!($charge['approved'] ?? false)) {
            return response()->json(['error' => true, 'step' => 'PAYMENT', 'message' => 'Pago rechazado', 'details' => $charge], 402);
        }

        // 5) Proceso con checkpoints
        $order = null;

        try {
            // E1: Crear la orden (fuera de transacción para poder reportar bien si falla)
            try {
                $order = Order::create([
                    'user_id'        => $user->id,
                    'status'         => 'PAID',
                    'subtotal'       => $subtotal,
                    'shipping'       => $shipping,
                    'taxes'          => $taxes,
                    'total'          => $total,
                    'payment_method' => $validated['payment_method'],
                    'street'         => $validated['street']  ?? null,
                    'city'           => $validated['city']    ?? null,
                    'state'          => $validated['state']   ?? null,
                    'zip_code'       => $validated['zip_code']?? null,
                    'country'        => $validated['country'] ?? null,
                ]);
            } catch (QueryException $qe) {
                return response()->json([
                    'error' => true,
                    'step'  => 'E1_ORDER_CREATE',
                    'message' => 'Falló la creación de la orden',
                    'payload' => [
                        'user_id' => $user->id,
                        'subtotal' => $subtotal,
                        'shipping' => $shipping,
                        'taxes' => $taxes,
                        'total' => $total,
                        'payment_method' => $validated['payment_method'],
                    ],
                    'sql_state' => $qe->errorInfo[0] ?? null,
                    'sql_code'  => $qe->errorInfo[1] ?? null,
                    'sql_detail'=> $qe->errorInfo[2] ?? null,
                ], 500);
            }

            // Ahora sí, lo demás dentro de una transacción para mantener atomicidad de ítems/stock/transacción
            DB::beginTransaction();

            // E2: Crear items (si falla uno, devolvemos detalle del que falló)
            foreach ($cart->items as $idx => $item) {
                $product   = $item->product;
                $unitPrice = $product->discount_price ?? $product->price;

                if ($product->stock !== null && $product->stock < $item->quantity) {
                    DB::rollBack();
                    // limpiamos la orden creada para no dejar basura:
                    $order->delete();
                    return response()->json([
                        'error' => true,
                        'step'  => 'E2_STOCK',
                        'message' => "Stock insuficiente para {$product->name}",
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'stock' => $product->stock,
                        'requested_qty' => $item->quantity,
                    ], 422);
                }

                try {
                    OrderItem::create([
                        'order_id'     => $order->id,
                        'product_id'   => $product->id,
                        'store_id'     => $product->store_id,
                        'quantity'     => $item->quantity,
                        'unit_price'   => $unitPrice,
                        'discount_pct' => 0,
                    ]);
                } catch (QueryException $qe) {
                    DB::rollBack();
                    $order->delete();
                    return response()->json([
                        'error' => true,
                        'step'  => 'E2_ITEM_CREATE',
                        'message' => 'Falló la creación de un ítem',
                        'which_item_index' => $idx,
                        'order_id'   => $order->id,
                        'product_id' => $product->id,
                        'store_id'   => $product->store_id,
                        'unit_price' => $unitPrice,
                        'quantity'   => $item->quantity,
                        'sql_state'  => $qe->errorInfo[0] ?? null,
                        'sql_code'   => $qe->errorInfo[1] ?? null,
                        'sql_detail' => $qe->errorInfo[2] ?? null,
                    ], 500);
                }

                // Descontar stock (si corresponde)
                if ($product->stock !== null) {
                    $product->decrement('stock', $item->quantity);
                }
            }

            // E3: Crear transacción contable
            try {
                Transaction::create([
                    'user_id'     => $user->id,
                    'order_id'    => $order->id,
                    'type'        => 'INCOME',   // caben en 10 chars
                    'amount'      => $total,
                    'currency'    => $currency,
                    'description' => 'Pago aprobado vía ' . $validated['payment_method'],
                ]);
            } catch (QueryException $qe) {
                DB::rollBack();
                $order->delete();
                return response()->json([
                    'error' => true,
                    'step'  => 'E3_TX_CREATE',
                    'message' => 'Falló la creación de la transacción',
                    'order_id'   => $order->id,
                    'amount'     => $total,
                    'currency'   => $currency,
                    'sql_state'  => $qe->errorInfo[0] ?? null,
                    'sql_code'   => $qe->errorInfo[1] ?? null,
                    'sql_detail' => $qe->errorInfo[2] ?? null,
                ], 500);
            }

            // E4: Limpiar carrito
            try {
                $cart->items()->delete();
            } catch (\Throwable $t) {
                DB::rollBack();
                $order->delete();
                return response()->json([
                    'error' => true,
                    'step'  => 'E4_CART_CLEAR',
                    'message' => 'Falló al limpiar el carrito',
                    'order_id' => $order->id,
                    'exception' => get_class($t),
                    'detail' => $t->getMessage(),
                ], 500);
            }

            DB::commit();

            // Respuesta OK
            return response()->json([
                'message' => 'Orden creada y pago aprobado',
                'order'   => $order->load(['items.product:id,name,image_1_url,price,discount_price']),
                'payment_result' => $charge,
            ], 201);

        } catch (\Throwable $e) {
            // Si algo escapa, aseguramos consistencia
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            if ($order && $order->exists) {
                $order->delete();
            }

            $payload = [
                'error' => true,
                'step'  => 'E_UNCAUGHT',
                'message' => 'Error no controlado en checkout',
                'exception' => get_class($e),
                'detail'    => $e->getMessage(),
            ];

            if ($e instanceof QueryException) {
                $payload['sql_state']  = $e->errorInfo[0] ?? null;
                $payload['sql_code']   = $e->errorInfo[1] ?? null;
                $payload['sql_detail'] = $e->errorInfo[2] ?? null;
            }

            return response()->json($payload, 500);
        }
    }
}
