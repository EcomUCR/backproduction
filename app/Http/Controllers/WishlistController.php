<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WishlistController extends Controller
{
    // Get all wishlists with items.
    public function index()
    {
        $wishlists = Wishlist::with('items.product')->get();
        return response()->json($wishlists);
    }

    // Get authenticated user's wishlist.
    public function me(Request $request)
    {
        $wishlist = Wishlist::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['slug' => Str::uuid(), 'is_public' => true]
        );

        $wishlist->load(['items.product:id,name,image_1_url,price,discount_price,stock']);
        return response()->json($wishlist);
    }

    // Create a new wishlist.
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

    // Delete a wishlist.
    public function destroy($id)
    {
        $wishlist = Wishlist::findOrFail($id);
        $wishlist->delete();
        return response()->json(null, 204);
    }

    // Clear items from authenticated user's wishlist.
    public function clear(Request $request)
    {
        $wishlist = Wishlist::where('user_id', $request->user()->id)->first();
        if (!$wishlist) return response()->json(null, 204);

        $wishlist->items()->delete();
        return response()->json(['ok' => true]);
    }

    // Get public wishlist by slug.
    public function showPublic($slug)
    {
        $wishlist = Wishlist::where('slug', $slug)
            ->where('is_public', true)
            ->with(['user:id,username,image', 'items.product:id,name,image_1_url,price,discount_price,stock'])
            ->firstOrFail();

        return response()->json($wishlist);
    }

    // Toggle wishlist public/private visibility.
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
