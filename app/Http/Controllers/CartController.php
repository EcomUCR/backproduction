<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Illuminate\Http\Request;
use App\Models\Product;

class CartController extends Controller
{
    public function index()
    {
        return response()->json(Cart::all());
    }

    // 🛒 Agregar producto al carrito
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
            $cart->items()->create([
                'product_id' => $request->product_id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice
            ]);
        }

        $cart->load(['items.product.store:id,name']);

        return response()->json([
            'message' => 'Producto añadido al carrito correctamente',
            'cart' => $cart
        ]);
    }

    public function show($id)
    {
        return response()->json(Cart::findOrFail($id));
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

    // 👤 Obtener carrito del usuario autenticado
    public function me(Request $request)
    {
        $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);

        $cart->load([
            'items.product' => function ($q) {
                $q->select('id', 'store_id', 'name', 'image_1_url', 'price', 'discount_price', 'stock', 'status');
            },
            'items.product.store' => function ($q) {
                $q->select('id', 'name');
            },
        ]);

        return response()->json($cart);
    }

    // 🧹 Vaciar carrito
    public function clear(Request $request)
    {
        try {
            $user = $request->user();
            $cart = Cart::where('user_id', $user->id)->first();

            if (!$cart) {
                return response()->json([
                    'ok' => false,
                    'message' => 'El usuario no tiene un carrito activo',
                ], 404);
            }

            $cart->items()->delete();

            $cart->load(['items.product.store:id,name']);

            return response()->json([
                'ok' => true,
                'message' => 'Carrito vaciado correctamente 🧹',
                'cart' => $cart,
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

    // ✏️ Actualizar cantidad
    public function updateItem(Request $request, $id)
    {
        $request->validate(['quantity' => 'required|integer|min:1']);
        $user = $request->user();

        $cart = Cart::where('user_id', $user->id)->firstOrFail();
        $item = $cart->items()->where('id', $id)->firstOrFail();

        $item->update(['quantity' => $request->quantity]);

        $cart->load(['items.product.store:id,name']);
        return response()->json(['message' => 'Cantidad actualizada', 'cart' => $cart]);
    }

    // ❌ Eliminar producto
    public function removeItem($id)
    {
        $user = request()->user();
        $cart = Cart::where('user_id', $user->id)->firstOrFail();
        $item = $cart->items()->where('id', $id)->firstOrFail();
        $item->delete();

        $cart->load(['items.product.store:id,name']);
        return response()->json(['message' => 'Producto eliminado', 'cart' => $cart]);
    }

    // 💰 Totales del carrito
    public function totals(Request $request)
    {
        $user = $request->user();
        $cart = Cart::where('user_id', $user->id)
            ->with('items.product')
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

            // 🔸 Ignorar productos archivados
            if (!$product || $product->status === 'ARCHIVED') {
                continue;
            }

            $price = ($product->discount_price && $product->discount_price > 0)
                ? $product->discount_price
                : $product->price;

            $subtotal += $price * $item->quantity;

            // 🔹 Actualizar el precio unitario del ítem
            $item->update(['unit_price' => $price]);
        }

        // Si no hay productos activos, devolver totales en cero
        if ($subtotal <= 0) {
            return response()->json([
                'message' => 'No hay productos activos en el carrito',
                'totals' => [
                    'subtotal' => 0,
                    'taxes' => 0,
                    'shipping' => 0,
                    'total' => 0,
                    'currency' => 'CRC',
                ],
            ]);
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
            // Solo contar productos no archivados
            'items_count' => $cart->items->where('product.status', '!=', 'ARCHIVED')->count(),
        ]);
    }

}
