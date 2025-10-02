<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use Illuminate\Http\Request;

class CartItemController extends Controller
{
    public function index()
    {
        $cartItems = CartItem::all();
        return response()->json($cartItems);
    }

    public function show($id)
    {
        $cartItem = CartItem::findOrFail($id);
        return response()->json($cartItem);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'cart_id' => 'required|exists:carts,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer',
            'unit_price' => 'required|numeric',
        ]);

        $cartItem = CartItem::create($validatedData);

        return response()->json($cartItem, 201);
    }

    public function update(Request $request, $id)
    {
        $cartItem = CartItem::findOrFail($id);

        $validatedData = $request->validate([
            'cart_id' => 'sometimes|exists:carts,id',
            'product_id' => 'sometimes|exists:products,id',
            'quantity' => 'sometimes|integer',
            'unit_price' => 'sometimes|numeric',
        ]);

        $cartItem->update($validatedData);

        return response()->json($cartItem);
    }

    public function destroy($id)
    {
        $cartItem = CartItem::findOrFail($id);
        $cartItem->delete();

        return response()->json(null, 204);
    }
}