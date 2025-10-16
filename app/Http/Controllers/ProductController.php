<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    // 📦 Muestra todos (incluye archivados — solo para debug o admin)
    public function index()
    {
        $products = Product::with(['store:id,name', 'categories'])->get();
        return response()->json($products);
    }

    // 🔍 Mostrar producto (NO muestra archivados)
    public function show($id)
    {
        $product = Product::with(['store:id,name', 'categories'])
            ->where('id', $id)
            ->where('status', '!=', 'ARCHIVED')
            ->firstOrFail();

        return response()->json($product);
    }

    // 🏪 Productos destacados (solo activos y no archivados)
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
            return response()->json(['message' => 'No hay productos disponibles para esta tienda'], 404);
        }

        return response()->json($products);
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
            return response()->json(['message' => 'No hay productos destacados en esta tienda'], 404);
        }

        return response()->json($featured);
    }
}
