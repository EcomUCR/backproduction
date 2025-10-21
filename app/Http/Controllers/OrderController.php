<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * 📦 Devuelve las órdenes del usuario autenticado con sus productos.
     */
    public function index(Request $request)
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->with([
                'items.product' => function ($query) {
                    $query->select('id', 'store_id', 'name', 'price', 'discount_price', 'image_1_url');
                },
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($orders);
    }

    /**
     * 🔍 Devuelve una orden específica con sus productos asociados.
     */
    public function show($id)
    {
        $order = Order::with([
            'items.product' => function ($query) {
                $query->select('id', 'store_id', 'name', 'price', 'discount_price', 'image_1_url');
            },
        ])->findOrFail($id);

        return response()->json($order);
    }

    /**
     * 🧾 Crea una nueva orden con sus productos (OrderItems).
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
            'items' => 'nullable|array',
        ]);

        // Crear orden principal
        $order = Order::create($validatedData);

        // Crear los items asociados
        if (!empty($request->items)) {
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
            'message' => 'Orden creada exitosamente ✅',
            'order' => $order->load('items.product'),
        ], 201);
    }

    /**
     * ✏️ Actualiza una orden existente.
     */
    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $validatedData = $request->validate([
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
            'message' => 'Orden actualizada correctamente ✏️',
            'order' => $order->load('items.product'),
        ]);
    }

    /**
     * ❌ Elimina una orden junto con sus productos.
     */
    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        $order->items()->delete();
        $order->delete();

        return response()->json(['message' => 'Orden eliminada correctamente 🗑️'], 204);
    }

    /**
     * 👤 Muestra todas las órdenes de un usuario específico.
     */
    public function userOrders($userId)
    {
        $orders = Order::where('user_id', $userId)
            ->with([
                'items.product' => function ($query) {
                    $query->select('id', 'store_id', 'name', 'price', 'discount_price', 'image_1_url');
                },
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($orders);
    }
}
