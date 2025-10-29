<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use Illuminate\Http\Request;

class CartItemController extends Controller
{
    // Retrieve and return all cart items with their associated products and stores.
    public function index()
    {
        $cartItems = CartItem::with('product.store')->get();
        return response()->json($cartItems);
    }

    // Retrieve and return a specific cart item by its ID, including product and store details.
    public function show($id)
    {
        $cartItem = CartItem::with('product.store')->findOrFail($id);
        return response()->json($cartItem);
    }

    // Create a new cart item with the provided data and load its product and store relationship.
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'cart_id' => 'required|exists:carts,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer',
            'unit_price' => 'required|numeric',
        ]);

        $cartItem = CartItem::create($validatedData)->load('product.store');

        return response()->json($cartItem, 201);
    }

    // Update an existing cart item with the provided data and reload its product and store relationship.
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

        $cartItem->load('product.store');

        return response()->json($cartItem);
    }

    // Delete a cart item.
    public function destroy($id)
    {
        $cartItem = CartItem::findOrFail($id);
        $cartItem->delete();

        return response()->json(null, 204);
    }
}
