<?php

namespace App\Http\Controllers;

use App\Models\StoreCategory;
use Illuminate\Http\Request;

class StoreCategoryController extends Controller
{
    public function index()
    {
        $storeCategories = StoreCategory::all();
        return response()->json($storeCategories);
    }

    public function show($id)
    {
        $storeCategory = StoreCategory::findOrFail($id);
        return response()->json($storeCategory);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:50|unique:store_categories',
        ]);

        $storeCategory = StoreCategory::create($validatedData);

        return response()->json($storeCategory, 201);
    }

    public function update(Request $request, $id)
    {
        $storeCategory = StoreCategory::findOrFail($id);

        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:50|unique:store_categories,name,'.$storeCategory->id,
        ]);

        $storeCategory->update($validatedData);

        return response()->json($storeCategory);
    }

    public function destroy($id)
    {
        $storeCategory = StoreCategory::findOrFail($id);
        $storeCategory->delete();

        return response()->json(null, 204);
    }
}