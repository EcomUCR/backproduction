<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use App\Models\WishlistItem;
use Illuminate\Http\Request;

class WishlistItemController extends Controller
{
    public function addItem(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $user = $request->user();
        $wishlist = Wishlist::firstOrCreate(['user_id' => $user->id]);

        $exists = $wishlist->items()->where('product_id', $request->product_id)->exists();
        if ($exists) {
            return response()->json(['message' => 'El producto ya está en tu wishlist']);
        }

        $item = $wishlist->items()->create([
            'product_id' => $request->product_id
        ]);

        $wishlist->load('items.product');

        return response()->json([
            'message' => 'Producto añadido a la wishlist correctamente',
            'wishlist' => $wishlist
        ]);
    }

    public function removeItem($id)
    {
        $user = request()->user();
        $wishlist = Wishlist::where('user_id', $user->id)->firstOrFail();
        $item = $wishlist->items()->where('id', $id)->firstOrFail();
        $item->delete();

        $wishlist->load('items.product');
        return response()->json(['message' => 'Producto eliminado de la wishlist', 'wishlist' => $wishlist]);
    }

    public function index()
    {
        $items = WishlistItem::with('product')->get();
        return response()->json($items);
    }

    public function destroy($id)
    {
        $wishlistItem = WishlistItem::findOrFail($id);
        $wishlistItem->delete();
        return response()->json(null, 204);
    }
}
