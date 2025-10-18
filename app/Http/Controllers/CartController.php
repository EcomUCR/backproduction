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

        $product = \App\Models\Product::findOrFail($request->product_id);
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
        $cart->load(['items.product.store:id,name']); // 👈 Incluye también la tienda

        return response()->json($cart);
    }


    public function clear(Request $request)
    {
        try {
            $user = $request->user();

            // Buscar el carrito del usuario autenticado
            $cart = Cart::where('user_id', $user->id)->first();

            if (!$cart) {
                return response()->json([
                    'ok' => false,
                    'message' => 'El usuario no tiene un carrito activo',
                ], 404);
            }

            // Verificar si hay items
            if ($cart->items()->count() === 0) {
                return response()->json([
                    'ok' => true,
                    'message' => 'El carrito ya estaba vacío 🧹',
                ], 200);
            }

            // Eliminar todos los items
            $cart->items()->delete();

            return response()->json([
                'ok' => true,
                'message' => 'Carrito vaciado correctamente 🧹',
            ], 200);
        } catch (\Exception $e) {
            \Log::error("❌ Error al limpiar el carrito: " . $e->getMessage());

            return response()->json([
                'ok' => false,
                'message' => 'Error interno al limpiar el carrito',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function updateItem(Request $request, $id)
    {
        $request->validate(['quantity' => 'required|integer|min:1']);
        $user = $request->user();

        $cart = Cart::where('user_id', $user->id)->firstOrFail();
        $item = $cart->items()->where('id', $id)->firstOrFail();

        $item->update(['quantity' => $request->quantity]);

        $cart->load('items.product.store');
        return response()->json(['message' => 'Cantidad actualizada', 'cart' => $cart]);
    }

    // DELETE /cart/item/{id}
    public function removeItem($id)
    {
        $user = request()->user();
        $cart = Cart::where('user_id', $user->id)->firstOrFail();
        $item = $cart->items()->where('id', $id)->firstOrFail();
        $item->delete();

        $cart->load('items.product.store');
        return response()->json(['message' => 'Producto eliminado', 'cart' => $cart]);
    }

    public function totals(Request $request)
    {
        $user = $request->user();

        $cart = \App\Models\Cart::where('user_id', $user->id)
            ->with('items.product.store')
            ->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json([
                'message' => 'El carrito está vacío',
                'totals' => [
                    'subtotal' => 0,
                    'taxes' => 0,
                    'shipping' => 0,
                    'total' => 0,
                    'currency' => 'CRC',
                ],
            ]);
        }

        $subtotal = 0;

        foreach ($cart->items as $item) {
            $product = $item->product;

            $price = ($product->discount_price !== null && $product->discount_price > 0)
                ? $product->discount_price
                : $product->price;

            $subtotal += $price * $item->quantity;

            $item->update(['unit_price' => $price]);
        }
        $taxes = round($subtotal * 0.13, 2);
        $shipping = 3000;
        $total = $subtotal + $taxes + $shipping;

        return response()->json([
            'subtotal' => round($subtotal, 2),
            'taxes' => $taxes,
            'shipping' => $shipping,
            'total' => round($total, 2),
            'currency' => 'CRC',
            'items_count' => $cart->items->count(),
        ]);
    }
}