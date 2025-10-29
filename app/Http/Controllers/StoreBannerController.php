<?php

namespace App\Http\Controllers;

use App\Models\StoreBanner;
use Illuminate\Http\Request;

class StoreBannerController extends Controller
{
    // List all store banners.
    public function index()
    {
        $storeBanners = StoreBanner::all();
        return response()->json($storeBanners);
    }

    // Show a specific store banner.
    public function show($id)
    {
        $storeBanner = StoreBanner::findOrFail($id);
        return response()->json($storeBanner);
    }

    // Create a new store banner.
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'type' => 'required|string|in:SMALL,MAIN,PROMO',
            'image' => 'required|string',
            'link' => 'nullable|string',
            'position' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $storeBanner = StoreBanner::create($validatedData);

        return response()->json($storeBanner, 201);
    }

    // Update an existing store banner.
    public function update(Request $request, $id)
    {
        $storeBanner = StoreBanner::findOrFail($id);

        $validatedData = $request->validate([
            'store_id' => 'sometimes|exists:stores,id',
            'type' => 'sometimes|string|in:SMALL,MAIN,PROMO',
            'image' => 'sometimes|string',
            'link' => 'nullable|string',
            'position' => 'nullable|integer',
            'is_active' => 'sometimes|boolean',
        ]);

        $storeBanner->update($validatedData);

        return response()->json($storeBanner);
    }

    // Delete a store banner.
    public function destroy($id)
    {
        $storeBanner = StoreBanner::findOrFail($id);
        $storeBanner->delete();

        return response()->json(null, 204);
    }
}