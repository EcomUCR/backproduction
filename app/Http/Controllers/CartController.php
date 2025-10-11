<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Illuminate\Http\Request;
use App\Models\Product;

class CartController extends Controller
{
    public function index()
    {
        $carts = Cart::all();
        return response()->json($carts);
    }

    public function addItem(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'nullable|integer|min:1'
        ]);

        $user = $request->user();
        $cart = Cart::firstOrCreate(['user_id' => $user->id]);
        $quantity = $request->quantity ?? 1;

        $product = Product::findOrFail($request->product_id);
        $unitPrice = $product->discount_price ?? $product->price;

        $item = $cart->items()->where('product_id', $request->product_id)->first();

        if ($item) {
            $item->update(['quantity' => $item->quantity + $quantity]);
        } else {
            $item = $cart->items()->create([
                'product_id' => $request->product_id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice
            ]);
        }

        // ✅ Cargar productos y sus tiendas
        $cart->load('items.product.store');

        return response()->json([
            'message' => 'Producto añadido al carrito correctamente',
            'cart' => $cart
        ]);
    }

    public function show($id)
    {
        $cart = Cart::findOrFail($id);
        return response()->json($cart);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $cart = Cart::create($validatedData);

        return response()->json($cart, 201);
    }

    public function update(Request $request, $id)
    {
        $cart = Cart::findOrFail($id);

        $validatedData = $request->validate([
            'user_id' => 'sometimes|exists:users,id',
        ]);

        $cart->update($validatedData);

        return response()->json($cart);
    }

    public function destroy($id)
    {
        $cart = Cart::findOrFail($id);
        $cart->delete();

        return response()->json(null, 204);
    }

    public function me(Request $request)
    {
        $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);
        // ✅ incluir tienda en cada producto
        $cart->load(['items.product.store' => function ($query) {
            $query->select('id', 'name', 'image_1_url', 'price', 'discount_price', 'stock', 'store_id');
        }]);
        return response()->json($cart);
    }

    public function clear(Request $request)
    {
        $cart = Cart::where('user_id', $request->user()->id)->first();
        if (!$cart)
            return response()->json(null, 204);

        $cart->items()->delete();
        return response()->json(['ok' => true]);
    }

    public function updateItem(Request $request, $id)
    {
        $request->validate(['quantity' => 'required|integer|min:1']);
        $user = $request->user();

        $cart = Cart::where('user_id', $user->id)->firstOrFail();
        $item = $cart->items()->where('id', $id)->firstOrFail();

        $item->update(['quantity' => $request->quantity]);

        // ✅ incluir tienda
        $cart->load('items.product.store');
        return response()->json(['message' => 'Cantidad actualizada', 'cart' => $cart]);
    }

    public function removeItem($id)
    {
        $user = request()->user();
        $cart = Cart::where('user_id', $user->id)->firstOrFail();
        $item = $cart->items()->where('id', $id)->firstOrFail();
        $item->delete();

        // ✅ incluir tienda
        $cart->load('items.product.store');
        return response()->json(['message' => 'Producto eliminado', 'cart' => $cart]);
    }
}
