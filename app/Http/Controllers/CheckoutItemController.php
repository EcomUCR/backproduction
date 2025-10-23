<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckoutItemController extends Controller
{
    /**
     * 📦 Añade productos a una orden existente.
     * Además, retorna los detalles completos de producto y tienda.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.store_id' => 'nullable|integer|exists:stores,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_pct' => 'nullable|numeric|min:0',
        ]);

        try {
            $order = Order::findOrFail($validated['order_id']);
            $createdItems = [];

            foreach ($validated['items'] as $index => $item) {
                // 🔹 Sanitizar tipos
                $unitPrice = isset($item['unit_price']) ? (float)$item['unit_price'] : 0;
                $quantity = isset($item['quantity']) ? (int)$item['quantity'] : 1;
                $discount = isset($item['discount_pct']) ? (float)$item['discount_pct'] : 0;

                // 🧾 Crear el item
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => (int)$item['product_id'],
                    'store_id' => $item['store_id'] ?? null,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount_pct' => $discount,
                ]);

                // 🔍 Registrar logs si algo sale raro
                if ($unitPrice === 0) {
                    \Log::warning("⚠️ Item sin precio unitario", [
                        'index' => $index,
                        'product_id' => $item['product_id'],
                        'data' => $item,
                    ]);
                }

                $createdItems[] = $orderItem;
            }


            return response()->json([
                'success' => true,
                'message' => 'Productos añadidos correctamente 🧩',
                'items' => $createdItems,
            ], 201);
        } catch (\Throwable $e) {
            Log::error('❌ Error al añadir productos a la orden', [
                'order_id' => $request->order_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => true,
                'message' => 'Error al añadir productos a la orden',
                'detail' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
            ], 500);
        }
    }
}
