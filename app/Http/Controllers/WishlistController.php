<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use App\Models\WishlistItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WishlistController extends Controller
{
    // 🔹 Obtener todas las wishlists con items y productos
    public function index()
    {
        $wishlists = Wishlist::with([
            'items.product' => function ($query) {
                $query->select('id', 'store_id', 'name', 'image_1_url', 'price', 'discount_price', 'stock')
                      ->with(['store:id,name']);
            }
        ])->get();

        return response()->json($wishlists);
    }

    // 🔹 Obtener la wishlist del usuario autenticado
    public function me(Request $request)
    {
        $wishlist = Wishlist::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['slug' => Str::uuid(), 'is_public' => true]
        );

        $wishlist->load([
            'items.product' => function ($query) {
                $query->select('id', 'store_id', 'name', 'image_1_url', 'price', 'discount_price', 'stock')
                      ->with(['store:id,name']);
            }
        ]);

        return response()->json($wishlist);
    }

    // 🔹 Crear una nueva wishlist manualmente
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'is_public' => 'nullable|boolean',
        ]);

        $wishlist = Wishlist::create([
            'user_id' => $validated['user_id'],
            'slug' => Str::uuid(),
            'is_public' => $validated['is_public'] ?? true,
        ]);

        return response()->json($wishlist, 201);
    }

    // 🔹 Agregar un producto a la wishlist del usuario autenticado
    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $wishlist = Wishlist::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['slug' => Str::uuid(), 'is_public' => true]
        );

        // Verificar si ya existe
        $exists = WishlistItem::where('wishlist_id', $wishlist->id)
            ->where('product_id', $request->product_id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'El producto ya está en la wishlist.'], 409);
        }

        $item = WishlistItem::create([
            'wishlist_id' => $wishlist->id,
            'product_id' => $request->product_id,
        ]);

        return response()->json($item, 201);
    }

    // 🔹 Eliminar un producto específico de la wishlist
    public function remove(Request $request, $itemId)
    {
        $wishlist = Wishlist::where('user_id', $request->user()->id)->firstOrFail();
        $item = WishlistItem::where('wishlist_id', $wishlist->id)
            ->where('id', $itemId)
            ->first();

        if (!$item) {
            return response()->json(['message' => 'Item no encontrado.'], 404);
        }

        $item->delete();
        return response()->json(['message' => 'Item eliminado.']);
    }

    // 🔹 Vaciar todos los items de la wishlist
    public function clear(Request $request)
    {
        $wishlist = Wishlist::where('user_id', $request->user()->id)->first();

        if (!$wishlist) {
            return response()->json(null, 204);
        }

        $wishlist->items()->delete();
        return response()->json(['ok' => true]);
    }

    // 🔹 Eliminar una wishlist completa
    public function destroy($id)
    {
        $wishlist = Wishlist::findOrFail($id);
        $wishlist->delete();
        return response()->json(null, 204);
    }

    // 🔹 Obtener wishlist pública por slug
    public function showPublic($slug)
    {
        $wishlist = Wishlist::where('slug', $slug)
            ->where('is_public', true)
            ->with([
                'user:id,username,image',
                'items.product' => function ($query) {
                    $query->select('id', 'store_id', 'name', 'image_1_url', 'price', 'discount_price', 'stock')
                          ->with(['store:id,name']);
                }
            ])
            ->firstOrFail();

        return response()->json($wishlist);
    }

    // 🔹 Alternar visibilidad pública/privada
    public function toggleVisibility(Request $request)
    {
        $wishlist = Wishlist::where('user_id', $request->user()->id)->firstOrFail();
        $wishlist->is_public = !$wishlist->is_public;
        $wishlist->save();

        return response()->json([
            'message' => 'Visibilidad actualizada',
            'is_public' => $wishlist->is_public,
        ]);
    }
}
