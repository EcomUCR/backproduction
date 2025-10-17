<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    // 📦 Muestra todos los productos (solo admin, incluye archivados)
    public function index()
    {
        $products = DB::table('products')
            ->join('stores', 'stores.id', '=', 'products.store_id')
            ->select('products.*', 'stores.name as store_name')
            ->get();

        return response()->json($products);
    }

    // 🔍 Mostrar un producto específico (excluye archivados)
    public function show($id)
    {
        $product = DB::table('products')
            ->join('stores', 'stores.id', '=', 'products.store_id')
            ->select('products.*', 'stores.name as store_name')
            ->where('products.id', '=', $id)
            ->whereRaw("TRIM(products.status)::text <> 'ARCHIVED'")
            ->first();

        if (!$product) {
            return response()->json(['message' => 'Producto no encontrado o archivado'], 404);
        }

        return response()->json($product);
    }

    // 🏪 Productos destacados (solo activos)
    public function featured()
    {
        $featured = DB::table('products')
            ->join('stores', 'stores.id', '=', 'products.store_id')
            ->select('products.*', 'stores.name as store_name')
            ->where('products.is_featured', '=', true)
            ->whereRaw("TRIM(products.status)::text = 'ACTIVE'")
            ->limit(10)
            ->get();

        return response()->json($featured);
    }

    // 🧩 No destacados (solo activos)
    public function notFeatured()
    {
        $notFeatured = DB::table('products')
            ->join('stores', 'stores.id', '=', 'products.store_id')
            ->select('products.*', 'stores.name as store_name')
            ->where('products.is_featured', '=', false)
            ->whereRaw("TRIM(products.status)::text = 'ACTIVE'")
            ->limit(10)
            ->get();

        return response()->json($notFeatured);
    }

    // 🏬 Productos por tienda (sin archivados)
    public function showByStore($store_id)
    {
        $products = DB::table('products')
            ->join('stores', 'stores.id', '=', 'products.store_id')
            ->select('products.*', 'stores.name as store_name')
            ->where('products.store_id', '=', $store_id)
            ->whereRaw("TRIM(products.status)::text <> 'ARCHIVED'")
            ->get();

        return response()->json($products);
    }

    // 🏷️ Productos por categoría (solo activos)
    public function byCategory($category_id)
    {
        $products = DB::table('products')
            ->join('product_category', 'products.id', '=', 'product_category.product_id')
            ->join('categories', 'categories.id', '=', 'product_category.category_id')
            ->join('stores', 'stores.id', '=', 'products.store_id')
            ->select('products.*', 'stores.name as store_name', 'categories.name as category_name')
            ->where('product_category.category_id', '=', $category_id)
            ->whereRaw("TRIM(products.status)::text = 'ACTIVE'")
            ->get();

        return response()->json($products);
    }

    // ⭐ Destacados por tienda (solo activos)
    public function featuredByStore($store_id)
    {
        $featured = DB::table('products')
            ->join('stores', 'stores.id', '=', 'products.store_id')
            ->select('products.*', 'stores.name as store_name')
            ->where('products.store_id', '=', $store_id)
            ->where('products.is_featured', '=', true)
            ->whereRaw("TRIM(products.status)::text = 'ACTIVE'")
            ->get();

        return response()->json($featured);
    }

    // 🛠️ Crear producto
    public function store(Request $request)
    {
        $validated = $request->validate([
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
        ]);

        $id = DB::table('products')->insertGetId($validated);

        return response()->json(DB::table('products')->find($id), 201);
    }

    // ✏️ Actualizar producto
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'sku' => 'sometimes|string|unique:products,sku,' . $id,
            'name' => 'sometimes|string|max:80',
            'description' => 'nullable|string',
            'details' => 'nullable|string',
            'price' => 'sometimes|numeric',
            'discount_price' => 'nullable|numeric',
            'stock' => 'nullable|integer',
            'status' => 'sometimes|string|in:ACTIVE,INACTIVE,ARCHIVED,DRAFT',
            'is_featured' => 'sometimes|boolean',
            'image_1_url' => 'sometimes|string',
        ]);

        DB::table('products')->where('id', '=', $id)->update($validated);

        return response()->json(DB::table('products')->find($id));
    }

    // ❌ Eliminar producto
    public function destroy($id)
    {
        DB::table('products')->where('id', '=', $id)->delete();
        return response()->json(null, 204);
    }
}
