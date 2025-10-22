<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckoutItemController extends Controller
{
    /**
     * 📦 Añade productos (items) a una orden existente.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_pct' => 'nullable|numeric|min:0',
            'items.*.store_id' => 'nullable|integer|exists:stores,id',
        ]);

        try {
            $order = Order::findOrFail($validated['order_id']);

            foreach ($validated['items'] as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'store_id' => $item['store_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount_pct' => $item['discount_pct'] ?? 0,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Productos añadidos a la orden 🧩',
                'order' => $order->load('items.product'),
            ], 201);
        } catch (\Throwable $e) {
            Log::error('❌ Error al añadir items a la orden', [
                'order_id' => $request->order_id ?? null,
                'error' => $e->getMessage(),
                'items' => $request->items,
            ]);

            return response()->json([
                'error' => true,
                'message' => 'Error al añadir productos a la orden',
                'detail' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
            ], 500);
        }
    }
}
