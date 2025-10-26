<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use Illuminate\Http\Request;

class OrderItemController extends Controller
{
    // Retrieve all order items.
    public function index()
    {
        $orderItems = OrderItem::all();
        return response()->json($orderItems);
    }

    // Retrieve a specific order item by ID.
    public function show($id)
    {
        $orderItem = OrderItem::findOrFail($id);
        return response()->json($orderItem);
    }

    // Create a new order item.
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'product_id' => 'required|exists:products,id',
            'store_id' => 'required|exists:stores,id',
            'quantity' => 'required|integer',
            'unit_price' => 'required|numeric',
            'discount_pct' => 'nullable|integer',
        ]);

        $orderItem = OrderItem::create($validatedData);

        return response()->json($orderItem, 201);
    }

    // Update an existing order item.
    public function update(Request $request, $id)
    {
        $orderItem = OrderItem::findOrFail($id);

        $validatedData = $request->validate([
            'order_id' => 'sometimes|exists:orders,id',
            'product_id' => 'sometimes|exists:products,id',
            'store_id' => 'sometimes|exists:stores,id',
            'quantity' => 'sometimes|integer',
            'unit_price' => 'sometimes|numeric',
            'discount_pct' => 'nullable|integer',
        ]);

        $orderItem->update($validatedData);

        return response()->json($orderItem);
    }

    // Delete an order item.
    public function destroy($id)
    {
        $orderItem = OrderItem::findOrFail($id);
        $orderItem->delete();

        return response()->json(null, 204);
    }
}