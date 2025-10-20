<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * 📦 Muestra todas las órdenes (puedes filtrar luego por usuario si lo deseas)
     */
    public function index()
    {
        $orders = Order::with(['items.product', 'user'])->get();
        return response()->json($orders);
    }

    /**
     * 🔍 Muestra una orden específica junto con sus productos.
     */
    public function show($id)
    {
        $order = Order::with(['items.product', 'user'])->findOrFail($id);
        return response()->json($order);
    }

    /**
     * 🧾 Crea una nueva orden y sus items (checkout con Stripe o manual).
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
            'status' => 'required|string|in:PENDING,PAID,CONFIRM,PROCESSING,SHIPPED,DELIVERED,CANCELLED',
            'subtotal' => 'required|numeric',
            'shipping' => 'required|numeric',
            'taxes' => 'required|numeric',
            'total' => 'required|numeric',
            'address_id' => 'nullable|exists:addresses,id',
            'street' => 'nullable|string|max:150',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'payment_method' => 'nullable|string|max:30',
            'payment_id' => 'nullable|string|max:100',
            'items' => 'nullable|array', // 👈 para crear los OrderItem
        ]);

        // 🧠 Crear la orden principal
        $order = Order::create($validatedData);

        // 🧩 Crear los OrderItem (productos del checkout)
        if ($request->has('items') && is_array($request->items)) {
            foreach ($request->items as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'store_id' => $item['store_id'] ?? null,
                    'quantity' => $item['quantity'] ?? 1,
                    'unit_price' => $item['unit_price'] ?? 0,
                    'discount_pct' => $item['discount_pct'] ?? 0,
                ]);
            }
        }

        return response()->json([
            'message' => 'Orden creada exitosamente',
            'order' => $order->load('items.product'),
        ], 201);
    }

    /**
     * ✏️ Actualiza los datos de una orden existente.
     */
    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
            'status' => 'required|string|in:PENDING,PAID,CONFIRM,PROCESSING,SHIPPED,DELIVERED,CANCELLED',
            'subtotal' => 'required|numeric',
            'shipping' => 'required|numeric',
            'taxes' => 'required|numeric',
            'total' => 'required|numeric',
            'address_id' => 'nullable|exists:addresses,id',
            'street' => 'nullable|string|max:150',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'payment_method' => 'nullable|string|max:30',
            'payment_id' => 'nullable|string|max:100',
        ]);

        $order->update($validatedData);

        return response()->json([
            'message' => 'Orden actualizada correctamente',
            'order' => $order->load('items.product'),
        ]);
    }

    /**
     * ❌ Elimina una orden y sus productos asociados.
     */
    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        $order->items()->delete(); // 🔹 elimina productos asociados
        $order->delete();

        return response()->json(['message' => 'Orden eliminada correctamente'], 204);
    }

    /**
     * ✅ (Opcional) Muestra todas las órdenes de un usuario.
     */
    public function userOrders($userId)
    {
        $orders = Order::with(['items.product'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($orders);
    }
}
