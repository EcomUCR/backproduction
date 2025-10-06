<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::all();
        return response()->json($products);
    }

    public function show($id)
    {
        $product = Product::findOrFail($id);
        return response()->json($product);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'sku' => 'required|string|max:30|unique:products',
            'name' => 'required|string|max:80',
            'image_url' => 'required|string',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'discount_price' => 'nullable|numeric',
            'stock' => 'nullable|integer',
            'status' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
        ]);

        if (empty($validatedData['discount_price']) && $validatedData['discount_price'] !== 0 && $validatedData['discount_price'] !== '0') {
            unset($validatedData['discount_price']);
        }

        $product = Product::create($validatedData);

        return response()->json($product, 201);
    }
    public function featured()
    {
        $featured = Product::where('is_featured', true)->limit(5)->get();
        return response()->json($featured);
    }
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validatedData = $request->validate([
            'store_id' => 'sometimes|exists:stores,id',
            'sku' => 'sometimes|string|max:30|unique:products,sku,' . $product->id,
            'name' => 'sometimes|string|max:80',
            'image_url' => 'sometimes|string',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric',
            'discount_price' => 'nullable|numeric',
            'stock' => 'nullable|integer',
            'status' => 'sometimes|boolean',
            'is_featured' => 'sometimes|boolean',
        ]);

        $product->update($validatedData);

        return response()->json($product);
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json(null, 204);
    }
}