<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('store', 'categories')->get();
        return response()->json($products);
    }

    public function show($id)
    {
        $product = Product::with(['store', 'categories'])->findOrFail($id);
        return response()->json($product);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'sku' => 'required|string|max:200|unique:products',
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
        $product->load('store', 'categories');

        return response()->json($product, 201);
    }
    public function featured()
    {
        $featured = Product::with('store', 'categories')->where('is_featured', true)->limit(10)->get();
        return response()->json($featured);
    }

    public function notFeatured()
    {
        $notFeatured = Product::with('store', 'categories')->where('is_featured', false)->limit(10)->get();
        return response()->json($notFeatured);
    }

    public function showByStore($store_id)
    {
    $products = Product::with('store', 'categories')->where('store_id', $store_id)->get();
    if ($products->isEmpty()) {
        return response()->json(['message' => 'No hay productos para esta tienda'], 404);
    }
    return response()->json($products);
    }

    public function update(Request $request, $id)
    {
        $product = Product::with('store', 'categories')->findOrFail($id);

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
        $product = Product::with('store', 'categories')->findOrFail($id);
        $product->delete();

        return response()->json(null, 204);
    }
}