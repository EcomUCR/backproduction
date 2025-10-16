<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    // 📦 Muestra todos (incluye archivados)
    public function index()
    {
        $products = Product::with(['store:id,name', 'categories'])->get();
        return response()->json($products);
    }

    // 🔍 Mostrar producto (oculta archivados)
    public function show($id)
    {
        $product = Product::with(['store:id,name', 'categories'])
            ->where('id', $id)
            ->where('status', '!=', 'ARCHIVED')
            ->first();

        if (!$product) {
            return response()->json(['message' => 'Producto no encontrado o archivado'], 404);
        }

        return response()->json($product);
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
            'status' => 'nullable|string|in:ACTIVE,INACTIVE,ARCHIVED,DRAFT',
            'is_featured' => 'nullable|boolean',
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'exists:categories,id',
        ]);

        $product = Product::create($validatedData);

        if (!empty($validatedData['category_ids'])) {
            $product->categories()->attach($validatedData['category_ids']);
        }

        $product->load('store', 'categories');

        return response()->json($product, 201);
    }

    // ⭐ Destacados (solo activos)
    public function featured()
    {
        $featured = Product::with('store', 'categories')
            ->where('is_featured', true)
            ->where('status', 'ACTIVE')
            ->limit(10)
            ->get();

        return response()->json($featured);
    }

    // 🧩 No destacados (solo activos)
    public function notFeatured()
    {
        $notFeatured = Product::with('store', 'categories')
            ->where('is_featured', false)
            ->where('status', 'ACTIVE')
            ->limit(10)
            ->get();

        return response()->json($notFeatured);
    }

    // 🏬 Por tienda (excluye archivados)
    public function showByStore($store_id)
    {
        $products = Product::with('store', 'categories')
            ->where('store_id', $store_id)
            ->where('status', '!=', 'ARCHIVED')
            ->get();

        return response()->json($products);
    }

    // ✏️ Actualizar
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
            'status' => 'sometimes|string|in:ACTIVE,INACTIVE,ARCHIVED,DRAFT',
            'is_featured' => 'sometimes|boolean',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        if ($request->has('category_ids')) {
            $product->categories()->sync($validatedData['category_ids']);
        }

        $product->update($validatedData);

        return response()->json($product);
    }

    // ❌ Eliminar
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();
        return response()->json(null, 204);
    }

    // 🏷️ Por categoría (solo activos)
    public function byCategory($category_id)
    {
        $products = Product::with('store', 'categories')
            ->whereHas('categories', fn($q) => $q->where('categories.id', $category_id))
            ->where('status', 'ACTIVE')
            ->get();

        return response()->json($products);
    }

    // ⭐ Destacados por tienda (solo activos)
    public function featuredByStore($store_id)
    {
        $featured = Product::with('store', 'categories')
            ->where('store_id', $store_id)
            ->where('is_featured', true)
            ->where('status', 'ACTIVE')
            ->get();

        return response()->json($featured);
    }
}
