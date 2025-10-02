<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function index()
    {
        $stores = Store::all();
        return response()->json($stores);
    }

    public function show($id)
    {
        $store = Store::findOrFail($id);
        return response()->json($store);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
            'name' => 'required|string|max:80',
            'slug' => 'required|string|max:100|unique:stores',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:store_categories,id',
            'business_name' => 'nullable|string|max:150',
            'tax_id' => 'nullable|string|max:50',
            'legal_type' => 'nullable|string|max:30',
            'support_email' => 'nullable|string|email|max:120',
            'support_phone' => 'nullable|string|max:30',
            'status' => 'nullable|string|in:ACTIVE,SUSPENDED,CLOSED',
        ]);

        $store = Store::create($validatedData);

        return response()->json($store, 201);
    }

    public function update(Request $request, $id)
    {
        $store = Store::findOrFail($id);

        $validatedData = $request->validate([
            'user_id' => 'sometimes|exists:users,id',
            'name' => 'sometimes|string|max:80',
            'slug' => 'sometimes|string|max:100|unique:stores,slug,'.$store->id,
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:store_categories,id',
            'business_name' => 'nullable|string|max:150',
            'tax_id' => 'nullable|string|max:50',
            'legal_type' => 'nullable|string|max:30',
            'support_email' => 'nullable|string|email|max:120',
            'support_phone' => 'nullable|string|max:30',
            'status' => 'nullable|string|in:ACTIVE,SUSPENDED,CLOSED',
        ]);

        $store->update($validatedData);

        return response()->json($store);
    }

    public function destroy($id)
    {
        $store = Store::findOrFail($id);
        $store->delete();

        return response()->json(null, 204);
    }
}