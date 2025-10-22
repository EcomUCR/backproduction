<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    /**
     * 💳 Procesa el checkout desde el frontend (Stripe, etc.)
     * Crea la orden + items + descuenta stock.
     */
    public function checkout(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => true, 'message' => 'Usuario no autenticado'], 401);
        }

        // ✅ Validar datos base de la orden
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'status' => 'required|string|in:PENDING,PAID,FAILED,CONFIRM,PROCESSING,SHIPPED,DELIVERED,CANCELLED',
            'subtotal' => 'required|numeric',
            'shipping' => 'nullable|numeric',
            'taxes' => 'nullable|numeric',
            'total' => 'required|numeric',
            'street' => 'nullable|string|max:150',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'payment_method' => 'required|string|max:30',
            'payment_id' => 'nullable|string|max:100',
            'currency' => 'nullable|string|max:10',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.store_id' => 'nullable|integer|exists:stores,id',
            'items.*.discount_pct' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            // 🧾 Crear la orden principal
            $order = Order::create([
                'user_id' => $validated['user_id'],
                'status' => $validated['status'],
                'subtotal' => $validated['subtotal'],
                'shipping' => $validated['shipping'] ?? 0,
                'taxes' => $validated['taxes'] ?? 0,
                'total' => $validated['total'],
                'street' => $validated['street'] ?? null,
                'city' => $validated['city'] ?? null,
                'state' => $validated['state'] ?? null,
                'zip_code' => $validated['zip_code'] ?? null,
                'country' => $validated['country'] ?? null,
                'payment_method' => strtoupper($validated['payment_method']),
                'payment_id' => $validated['payment_id'] ?? 'N/A',
            ]);

            // 🧩 Crear los OrderItems asociados
            foreach ($validated['items'] as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'store_id' => $item['store_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount_pct' => $item['discount_pct'] ?? 0,
                ]);

                // 🏷️ Descontar stock
                Product::where('id', $item['product_id'])->decrement('stock', $item['quantity']);
            }

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
