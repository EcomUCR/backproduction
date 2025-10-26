<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    // Retrieve all orders of the authenticated user along with their products.
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Usuario no autenticado'], 401);
        }

        $orders = Order::where('user_id', $user->id)
            ->with([
                'items.product:id,store_id,name,price,discount_price,image_1_url',
            ])
            ->orderByDesc('created_at')
            ->get();

        return response()->json($orders);
    }

    // Retrieve a specific order along with its associated products.
    public function show($id)
    {
        $order = Order::with([
            'items.product:id,store_id,name,price,discount_price,image_1_url',
        ])->findOrFail($id);

        return response()->json($order);
    }

    // Create a new order along with its associated items (OrderItems).
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

        $order = Order::create($validatedData);

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

    // Update an existing order.
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

    // Delete an order along with its associated items.
    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        $order->items()->delete();
        $order->delete();

        return response()->json(['message' => 'Orden eliminada correctamente 🗑️'], 204);
    }

    // Retrieve all orders for a specific user by the user's ID.
    public function userOrders($userId)
    {
        $orders = Order::where('user_id', $userId)
            ->with([
                'items.product:id,store_id,name,price,discount_price,image_1_url',
            ])
            ->orderByDesc('created_at')
            ->get();

        return response()->json($orders);
    }

    //Devuelve las órdenes que contienen productos de una tienda específica.
    public function storeOrders($storeId)
    {
        $orders = Order::whereHas('items', function ($q) use ($storeId) {
           $q->where('store_id', $storeId);
     })
        ->with(['items.product:id,store_id,name,price,discount_price,image_1_url'])
        ->orderByDesc('created_at')
        ->get();

        return response()->json($orders);
    }
}
