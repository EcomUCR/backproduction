<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use App\Models\WishlistItem;
use Illuminate\Http\Request;

class WishlistItemController extends Controller
{
    // Get all wishlist items with products.
    public function index()
    {
        $items = WishlistItem::with('product')->get();
        return response()->json($items);
    }

    // Add a product to authenticated user's wishlist.
    public function addItem(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $user = $request->user();
        $wishlist = Wishlist::firstOrCreate(['user_id' => $user->id]);

        $item = $wishlist->items()->where('product_id', $request->product_id)->first();

        if ($item) {
            return response()->json(['message' => 'El producto ya está en tu wishlist']);
        }

        $wishlist->items()->create(['product_id' => $request->product_id]);
        $wishlist->load('items.product');

        return response()->json([
            'message' => 'Producto añadido a la wishlist correctamente',
            'wishlist' => $wishlist,
        ]);
    }

    // Remove a product from authenticated user's wishlist.
    public function removeItem($id)
    {
        $user = request()->user();
        $wishlist = Wishlist::where('user_id', $user->id)->firstOrFail();
        $item = $wishlist->items()->where('id', $id)->firstOrFail();
        $item->delete();

        $wishlist->load('items.product');
        return response()->json([
            'message' => 'Producto eliminado de la wishlist',
            'wishlist' => $wishlist,
        ]);
    }

    // Delete a wishlist item by ID.
    public function destroy($id)
    {
        $wishlistItem = WishlistItem::findOrFail($id);
        $wishlistItem->delete();
        return response()->json(null, 204);
    }
}
