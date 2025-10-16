<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    // 📦 Muestra todos los productos (incluye archivados)
    public function index()
    {
        $products = Product::with(['store:id,name', 'categories'])->get();
        return response()->json($products);
    }

    // 🔍 Mostrar un producto específico (solo si no está archivado)
    public function show($id)
    {
        $product = Product::with(['store:id,name', 'categories'])
            ->where('id', $id)
            ->where('status', '!=', 'ARCHIVED')
            ->firstOrFail();

        return response()->json($product);
    }


    // 🏪 Productos destacados (sin archivados)
    public function featured()
    {
        $featured = Product::with('store', 'categories')
            ->where('is_featured', true)
            ->where('status', '!=', 'ARCHIVED')
            ->limit(10)
            ->get();

        return response()->json($featured);
    }

    // 🧩 Productos no destacados (sin archivados)
    public function notFeatured()
    {
        $notFeatured = Product::with('store', 'categories')
            ->where('is_featured', false)
            ->where('status', '!=', 'ARCHIVED')
            ->limit(10)
            ->get();

        return response()->json($notFeatured);
    }

    // 🏬 Productos por tienda (sin archivados)
    public function showByStore($store_id)
    {
        $products = Product::with('store', 'categories')
            ->where('store_id', $store_id)
            ->where('status', '!=', 'ARCHIVED')
            ->get();

        if ($products->isEmpty()) {
            return response()->json(['message' => 'No hay productos activos para esta tienda'], 404);
        }

        return response()->json($products);
    }

    // 🛠️ Crear producto
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

        if (!empty($validatedData['category_ids'])) {
            $product->categories()->attach($validatedData['category_ids']);
        }

        $product->load('store', 'categories');

        return response()->json($product, 201);
    }

    // ✏️ Actualizar producto
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
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        if ($request->has('category_ids') && is_array($request->category_ids)) {
            $product->categories()->sync($request->category_ids);
        }

        $product->update($validatedData);

        return response()->json($product);
    }

    // ❌ Eliminar producto
    public function destroy($id)
    {
        $product = Product::with('store', 'categories')->findOrFail($id);
        $product->delete();

        return response()->json(null, 204);
    }

    // 🏷️ Productos por categoría (sin archivados)
    public function byCategory($category_id)
    {
        $products = Product::with('store', 'categories')
            ->whereHas('categories', function ($query) use ($category_id) {
                $query->where('categories.id', $category_id);
            })
            ->where('status', '!=', 'ARCHIVED')
            ->get();

        if ($products->isEmpty()) {
            return response()->json(['message' => 'No hay productos activos en esta categoría'], 404);
        }

        return response()->json($products);
    }

    // ⭐ Productos destacados por tienda (sin archivados)
    public function featuredByStore($store_id)
    {
        $featured = Product::with('store', 'categories')
            ->where('store_id', $store_id)
            ->where('is_featured', true)
            ->where('status', '!=', 'ARCHIVED')
            ->get();

        if ($featured->isEmpty()) {
            return response()->json(['message' => 'No hay productos destacados activos en esta tienda'], 404);
        }

        return response()->json($featured);
    }
}
