<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index()
    {
        $wishlists = Wishlist::with('items.product')->get();
        return response()->json($wishlists);
    }

    public function me(Request $request)
    {
        $wishlist = Wishlist::firstOrCreate(['user_id' => $request->user()->id]);
        $wishlist->load(['items.product:id,name,image_1_url,price,discount_price,stock']);
        return response()->json($wishlist);
    }

    public function store(Request $request)
    {
        $validated = $request->validate(['user_id' => 'required|exists:users,id']);
        $wishlist = Wishlist::create($validated);
        return response()->json($wishlist, 201);
    }

    public function destroy($id)
    {
        $wishlist = Wishlist::findOrFail($id);
        $wishlist->delete();
        return response()->json(null, 204);
    }

    public function clear(Request $request)
    {
        $wishlist = Wishlist::where('user_id', $request->user()->id)->first();
        if (!$wishlist) return response()->json(null, 204);
        $wishlist->items()->delete();
        return response()->json(['ok' => true]);
    }
}
