<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use Illuminate\Http\Request;

class ProductCategoryController extends Controller
{
    public function index()
    {
        $productCategories = ProductCategory::all();
        return response()->json($productCategories);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'product_id' => 'required|exists:products,id',
            'category_id' => 'required|exists:categories,id',
        ]);

        $productCategory = ProductCategory::create($validatedData);

        return response()->json($productCategory, 201);
    }

    public function destroy($product_id, $category_id)
    {
        $productCategory = ProductCategory::where('product_id', $product_id)
                                          ->where('category_id', $category_id)
                                          ->firstOrFail();
        $productCategory->delete();

        return response()->json(null, 204);
    }
}