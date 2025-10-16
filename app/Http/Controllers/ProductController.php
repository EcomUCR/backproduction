<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with(['store:id,name', 'categories'])->get();
        return response()->json($products);
    }

    public function show($id)
    {
        $product = Product::with(['store:id,name', 'categories'])->findOrFail($id);
        return response()->json($product);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'sku' => 'required|string|unique:products',
            'name' => 'required|string|max:80',
            'image_1_url' => 'required|string',
            'image_2_url' => 'nullable|string',
            'image_3_url' => 'nullable|string',
            'description' => 'nullable|string',
            'details' => 'nullable|string',
            'price' => 'required|numeric',
            'discount_price' => 'nullable|numeric',
            'stock' => 'nullable|integer',
            'status' => 'sometimes|string|in:ACTIVE,INACTIVE,ARCHIVED',
            'is_featured' => 'nullable|boolean',
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'exists:categories,id',
        ]);

        if (empty($validatedData['discount_price']) && $validatedData['discount_price'] !== 0 && $validatedData['discount_price'] !== '0') {
            unset($validatedData['discount_price']);
        }

        $product = Product::create($validatedData);

        // ✅ Asociar categorías si vienen en el request
        if (!empty($validatedData['category_ids'])) {
            $product->categories()->attach($validatedData['category_ids']);
        }

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
            'sku' => 'sometimes|string|unique:products,sku,' . $product->id,
            'name' => 'sometimes|string|max:80',
            'image_1_url' => 'sometimes|string',
            'image_2_url' => 'nullable|string',
            'image_3_url' => 'nullable|string',
            'description' => 'nullable|string',
            'details' => 'nullable|string',
            'price' => 'sometimes|numeric',
            'discount_price' => 'nullable|numeric',
            'stock' => 'nullable|integer',
            'status' => 'sometimes|string|in:ACTIVE,INACTIVE,ARCHIVED',
            'is_featured' => 'sometimes|boolean',
        ]);

        if ($request->has('category_ids')) {
            $product->categories()->sync($validatedData['category_ids']);
        }

        $product->update($validatedData);

        return response()->json($product);
    }

    public function destroy($id)
    {
        $product = Product::with('store', 'categories')->findOrFail($id);
        $product->delete();

        return response()->json(null, 204);
    }

    public function byCategory($category_id)
    {
        $products = Product::with('store', 'categories')
            ->whereHas('categories', function ($query) use ($category_id) {
                $query->where('categories.id', $category_id);
            })
            ->get();

        if ($products->isEmpty()) {
            return response()->json(['message' => 'No hay productos en esta categoría'], 404);
        }

        return response()->json($products);
    }
    public function featuredByStore($store_id)
    {
        $featured = Product::with('store', 'categories')
            ->where('store_id', $store_id)
            ->where('is_featured', true)
            ->get();

        if ($featured->isEmpty()) {
            return response()->json(['message' => 'No hay productos destacados en esta tienda'], 404);
        }

        return response()->json($featured);
    }

}