<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    // 📦 Muestra todos los productos (incluye archivados solo si es admin)
    public function index()
    {
        $products = Product::with(['store:id,name', 'categories'])->get();
        return response()->json($products);
    }

    // 🔍 Mostrar un producto específico (solo si no está archivado)
    public function show($id)
    {
        $query = Product::with(['store:id,name', 'categories'])
            ->where('id', $id);

        // 🧠 Si NO hay usuario autenticado → solo mostrar ACTIVE
        if (!Auth::check()) {
            $query->where('status', 'ACTIVE');
        } 
        // 🧠 Si el usuario autenticado NO es el dueño → solo mostrar ACTIVE
        else if (Auth::user()->role !== 'SELLER') {
            $query->where('status', 'ACTIVE');
        } 
        // 🧠 Si es el dueño → mostrar todo menos ARCHIVED
        else {
            $query->where('status', '!=', 'ARCHIVED');
        }

        $product = $query->firstOrFail();

        return response()->json($product);
    }

    // 🏪 Productos destacados (solo activos)
    public function featured()
    {
        $featured = Product::with('store', 'categories')
            ->where('is_featured', true)
            ->where('status', 'ACTIVE')
            ->limit(10)
            ->get();

        return response()->json($featured);
    }

    // 🧩 Productos no destacados (solo activos)
    public function notFeatured()
    {
        $notFeatured = Product::with('store', 'categories')
            ->where('is_featured', false)
            ->where('status', 'ACTIVE')
            ->limit(10)
            ->get();

        return response()->json($notFeatured);
    }

    // 🏬 Productos por tienda
    public function showByStore($store_id)
    {
        $query = Product::with('store', 'categories')
            ->where('store_id', $store_id);

        // 🔹 Si el usuario autenticado es el dueño, mostrar todo menos ARCHIVED
        if (Auth::check() && Auth::user()->store && Auth::user()->store->id == $store_id) {
            $query->where('status', '!=', 'ARCHIVED');
        } 
        // 🔹 Si es visitante o cliente → solo ACTIVE
        else {
            $query->where('status', 'ACTIVE');
        }

        $products = $query->get();

        if ($products->isEmpty()) {
            return response()->json(['message' => 'No hay productos disponibles para esta tienda'], 404);
        }

        return response()->json($products);
    }

    // 🏷️ Productos por categoría (solo activos)
    public function byCategory($category_id)
    {
        $products = Product::with('store', 'categories')
            ->whereHas('categories', function ($query) use ($category_id) {
                $query->where('categories.id', $category_id);
            })
            ->where('status', 'ACTIVE')
            ->get();

        if ($products->isEmpty()) {
            return response()->json(['message' => 'No hay productos activos en esta categoría'], 404);
        }

        return response()->json($products);
    }

    // ⭐ Productos destacados por tienda
    public function featuredByStore($store_id)
    {
        $query = Product::with('store', 'categories')
            ->where('store_id', $store_id)
            ->where('is_featured', true);

        if (Auth::check() && Auth::user()->store && Auth::user()->store->id == $store_id) {
            $query->where('status', '!=', 'ARCHIVED');
        } else {
            $query->where('status', 'ACTIVE');
        }

        $featured = $query->get();

        if ($featured->isEmpty()) {
            return response()->json(['message' => 'No hay productos destacados disponibles'], 404);
        }

        return response()->json($featured);
    }
}
