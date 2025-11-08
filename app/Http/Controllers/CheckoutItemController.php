<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckoutItemController extends Controller
{
    /**
     * Añadir productos a una orden existente,
     * rebajar stock y devolver la información detallada.
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
                $product = Product::find($item['product_id']);

                if (!$product) {
                    Log::warning("⚠️ Producto no encontrado", [
                        'product_id' => $item['product_id'],
                    ]);
                    continue;
                }

                $unitPrice = (float) ($item['unit_price'] ?? 0);
                $quantity = (int) ($item['quantity'] ?? 1);
                $discount = (float) ($item['discount_pct'] ?? 0);

                // 🧮 Validar stock suficiente
                if ($product->stock < $quantity) {
                    Log::warning("⚠️ Stock insuficiente para producto {$product->id}", [
                        'stock_actual' => $product->stock,
                        'cantidad_solicitada' => $quantity,
                    ]);

                    return response()->json([
                        'error' => true,
                        'message' => "Stock insuficiente para el producto '{$product->name}'",
                        'available_stock' => $product->stock,
                    ], 400);
                }

                // 🧾 Crear OrderItem
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'store_id' => $item['store_id'] ?? null,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount_pct' => $discount,
                ]);

                // 🔻 Rebajar stock en base de datos
                $product->decrement('stock', $quantity);
                
                // 🔺 Incrementar contador de vendidos
                $product->increment('sold_count', $quantity);

                // 🚨 Advertencia si el precio unitario no fue definido
                if ($unitPrice === 0) {
                    Log::warning("⚠️ Item sin precio unitario", [
                        'index' => $index,
                        'product_id' => $product->id,
                        'data' => $item,
                    ]);
                }

                // 🔁 Cargar relación con producto (stock actualizado)
                $orderItem->load([
                    'product:id,name,stock,price,discount_price,image_1_url'
                ]);

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
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => true,
                'message' => 'Error al añadir productos a la orden',
                'detail' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
            ], 500);
        }
    }
}
