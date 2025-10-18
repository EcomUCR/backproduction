<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::all();
        return response()->json($orders);
    }

    public function show($id)
    {
        $order = Order::findOrFail($id);
        return response()->json($order);
    }

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
        ]);

        $order = Order::create($validatedData);

        return response()->json($order, 201);
    }

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

        return response()->json($order);
    }

    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        $order->delete();

        return response()->json(null, 204);
    }
}