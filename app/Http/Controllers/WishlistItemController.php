<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use App\Models\WishlistItem;
use Illuminate\Http\Request;

class WishlistItemController extends Controller
{
    public function index()
    {
        $items = WishlistItem::with('product.store')->get();
        return response()->json($items);
    }

    public function addItem(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);
        $user = $request->user();
        $wishlist = Wishlist::firstOrCreate(['user_id' => $user->id]);
        $item = $wishlist->items()->where('product_id', $request->product_id)->first();
        if ($item) {
            return response()->json([
                'message' => 'El producto ya está en tu wishlist'
            ], 200);
        }

        $wishlist->items()->create([
            'product_id' => $request->product_id
        ]);
        $wishlist->load('items.product.store');
        return response()->json([
            'message' => 'Producto añadido a la wishlist correctamente',
            'wishlist' => $wishlist,
        ], 201);
    }

    public function removeItem($id)
    {
        $user = request()->user();
        $wishlist = Wishlist::where('user_id', $user->id)->firstOrFail();
        $item = $wishlist->items()->where('id', $id)->firstOrFail();
        $item->delete();
        $wishlist->load('items.product.store');

        return response()->json([
            'message' => 'Producto eliminado de la wishlist',
            'wishlist' => $wishlist,
        ]);
    }

    public function destroy($id)
    {
        $wishlistItem = WishlistItem::findOrFail($id);
        $wishlistItem->delete();
        return response()->json([
            'message' => 'Wishlist item eliminado correctamente'
        ], 204);
    }
}
