<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    // 📦 Muestra todos los productos activos cuyas tiendas también estén activas
    public function index()
    {
        $products = DB::table('products')
            ->join('stores', 'stores.id', '=', 'products.store_id')
            ->select('products.*', 'stores.name as store_name')
            ->whereRaw("TRIM(products.status)::text = 'ACTIVE'")
            ->whereRaw("TRIM(stores.status)::text = 'ACTIVE'")
            ->where('stores.is_verified', true)
            ->whereRaw("TRIM(products.status)::text <> 'ARCHIVED'")
            ->orderByDesc('products.created_at')
            ->get();

        return response()->json($products);
    }

    // 🔍 Mostrar un producto específico (solo si la tienda está activa y no está archivado)
    public function show($id)
    {
        $product = DB::table('products')
            ->join('stores', 'stores.id', '=', 'products.store_id')
            ->select('products.*', 'stores.name as store_name')
            ->where('products.id', '=', $id)
            ->whereRaw("TRIM(products.status)::text <> 'ARCHIVED'")
            ->whereRaw("TRIM(stores.status)::text = 'ACTIVE'")
            ->first();

        if (!$product) {
            return response()->json(['message' => 'Producto no encontrado o la tienda está inactiva'], 404);
        }

        // 🔹 Agregar las categorías relacionadas
        $categories = DB::table('categories')
            ->join('product_category', 'categories.id', '=', 'product_category.category_id')
            ->where('product_category.product_id', '=', $id)
            ->select('categories.id', 'categories.name')
            ->get();

        $product->categories = $categories;

        return response()->json($product);
    }


    // 🏪 Productos destacados (solo activos y de tiendas activas)
    public function featured()
    {
        $featured = DB::table('products')
            ->join('stores', 'stores.id', '=', 'products.store_id')
            ->select('products.*', 'stores.name as store_name')
            ->where('products.is_featured', '=', true)
            ->whereRaw("TRIM(products.status)::text = 'ACTIVE'")
            ->whereRaw("TRIM(stores.status)::text = 'ACTIVE'")
            ->whereRaw("TRIM(products.status)::text <> 'ARCHIVED'")
            ->limit(10)
            ->get();

        return response()->json($featured);
    }

    // 🧩 No destacados (solo activos y de tiendas activas)
    public function notFeatured()
    {
        $notFeatured = DB::table('products')
            ->join('stores', 'stores.id', '=', 'products.store_id')
            ->select('products.*', 'stores.name as store_name')
            ->where('products.is_featured', '=', false)
            ->whereRaw("TRIM(products.status)::text = 'ACTIVE'")
            ->whereRaw("TRIM(stores.status)::text = 'ACTIVE'")
            ->whereRaw("TRIM(products.status)::text <> 'ARCHIVED'")
            ->limit(10)
            ->get();

        return response()->json($notFeatured);
    }

    // 🏬 Productos por tienda (solo si la tienda existe y está activa)
    // 🏬 Productos por tienda (vista pública - solo productos ACTIVOS y tienda verificada)
    public function showByStore($store_id)
    {
        $products = DB::table('products')
            ->join('stores', 'stores.id', '=', 'products.store_id')
            ->select('products.*', 'stores.name as store_name')
            ->where('products.store_id', '=', $store_id)
            ->whereRaw("TRIM(products.status)::text = 'ACTIVE'") // ✅ solo activos
            ->whereRaw("TRIM(stores.status)::text = 'ACTIVE'")  // ✅ tienda activa
            ->where('stores.is_verified', true)   // ✅ tienda verificada
            ->whereRaw("TRIM(products.status)::text <> 'ARCHIVED'")
            ->orderByDesc('products.created_at')
            ->get();

        return response()->json($products);
    }


    // 🏷️ Productos por categoría (solo activos y de tiendas activas)
    public function byCategory($category_id)
    {
        $products = DB::table('products')
            ->join('product_category', 'products.id', '=', 'product_category.product_id')
            ->join('categories', 'categories.id', '=', 'product_category.category_id')
            ->join('stores', 'stores.id', '=', 'products.store_id')
            ->select(
                'products.*',
                'stores.name as store_name',
                'categories.name as category_name'
            )
            ->where('product_category.category_id', '=', $category_id)
            ->whereRaw("TRIM(products.status)::text = 'ACTIVE'")
            ->whereRaw("TRIM(stores.status)::text = 'ACTIVE'")
            ->whereRaw("TRIM(products.status)::text <> 'ARCHIVED'")
            ->get();

        return response()->json($products);
    }

    // ⭐ Destacados por tienda (solo productos activos y tienda activa)
    public function featuredByStore($store_id)
    {
        $featured = DB::table('products')
            ->join('stores', 'stores.id', '=', 'products.store_id')
            ->select('products.*', 'stores.name as store_name')
            ->where('products.store_id', '=', $store_id)
            ->where('products.is_featured', '=', true)
            ->whereRaw("TRIM(products.status)::text = 'ACTIVE'")
            ->whereRaw("TRIM(stores.status)::text = 'ACTIVE'")
            ->whereRaw("TRIM(products.status)::text <> 'ARCHIVED'")
            ->where('stores.is_verified', true)
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
            'sold_count' => 0,
            'status' => 'nullable|string|in:ACTIVE,INACTIVE,ARCHIVED,DRAFT',
            'is_featured' => 'nullable|boolean',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        // 🔹 Guardar producto
        $id = DB::table('products')->insertGetId([
            'store_id' => $validated['store_id'],
            'sku' => $validated['sku'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'details' => $validated['details'] ?? null,
            'price' => $validated['price'],
            'discount_price' => $validated['discount_price'] ?? null,
            'stock' => $validated['stock'] ?? 0,
            'status' => $validated['status'] ?? 'ACTIVE',
            'is_featured' => $validated['is_featured'] ?? false,
            'image_1_url' => $validated['image_1_url'],
            'image_2_url' => $validated['image_2_url'] ?? null,
            'image_3_url' => $validated['image_3_url'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 🔹 Registrar categorías si vienen
        if (!empty($validated['category_ids'])) {
            foreach ($validated['category_ids'] as $categoryId) {
                DB::table('product_category')->insert([
                    'product_id' => $id,
                    'category_id' => $categoryId,
                ]);
            }
        }

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
            'image_1_url' => 'nullable|string',
            'image_2_url' => 'nullable|string',
            'image_3_url' => 'nullable|string',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        // ===============================
        // 🔹 Construcción dinámica del update
        // ===============================
        $fields = [
            'sku',
            'name',
            'description',
            'details',
            'price',
            'discount_price',
            'stock',
            'status',
            'is_featured',
            'image_1_url',
            'image_2_url',
            'image_3_url',
        ];

        $updateData = [];

        foreach ($fields as $field) {
            // ⚠️ Si el campo viene en el request (aunque sea null), se actualiza
            if ($request->has($field)) {
                $updateData[$field] = $request->input($field);
            }
        }

        $updateData['updated_at'] = now();

        // ===============================
        // 🔹 Actualización del producto
        // ===============================
        DB::table('products')
            ->where('id', $id)
            ->update($updateData);

        // ===============================
        // 🔹 Sincronizar categorías (si vienen en el request)
        // ===============================
        if ($request->has('category_ids')) {
            DB::table('product_category')->where('product_id', $id)->delete();

            if (!empty($validated['category_ids'])) {
                foreach ($validated['category_ids'] as $categoryId) {
                    DB::table('product_category')->insert([
                        'product_id' => $id,
                        'category_id' => $categoryId,
                    ]);
                }
            }
        }

        // ===============================
        // 🔹 Devolver producto actualizado
        // ===============================
        $updatedProduct = DB::table('products')->find($id);

        return response()->json($updatedProduct);
    }



    // ❌ Eliminar producto
    public function destroy($id)
    {
        DB::table('products')->where('id', '=', $id)->delete();
        return response()->json(null, 204);
    }

    // ================================================================
    // 🆕 NUEVOS MÉTODOS tipo StoreProductController (por tienda)
    // ================================================================

    // 📦 Todos los productos de una tienda (solo activos, tienda activa y verificada)
    public function indexByStore($store_id)
    {
        $products = DB::table('products')
            ->join('stores', 'stores.id', '=', 'products.store_id')
            ->select('products.*', 'stores.name as store_name')
            ->where('products.store_id', '=', $store_id)
            ->whereRaw("TRIM(products.status)::text = 'ACTIVE'")
            ->whereRaw("TRIM(products.status)::text <> 'ARCHIVED'")
            ->whereRaw("TRIM(stores.status)::text = 'ACTIVE'")
            ->where('stores.is_verified', true)
            ->orderByDesc('products.created_at')
            ->get();

        return response()->json($products);
    }

    // 🔍 Producto específico de una tienda (con validaciones)
    public function showByStoreProduct($store_id, $product_id)
    {
        $product = DB::table('products')
            ->join('stores', 'stores.id', '=', 'products.store_id')
            ->select('products.*', 'stores.name as store_name')
            ->where('products.store_id', '=', $store_id)
            ->where('products.id', '=', $product_id)
            ->whereRaw("TRIM(products.status)::text = 'ACTIVE'")
            ->whereRaw("TRIM(stores.status)::text = 'ACTIVE'")
            ->whereRaw("TRIM(products.status)::text <> 'ARCHIVED'")
            ->where('stores.is_verified', true)
            ->first();

        if (!$product) {
            return response()->json(['message' => 'Producto no encontrado o no disponible'], 404);
        }

        return response()->json($product);
    }

    // ⭐ Productos destacados por tienda (solo activos/verificados)
    public function featuredByStoreFull($store_id)
    {
        $featured = DB::table('products')
            ->join('stores', 'stores.id', '=', 'products.store_id')
            ->select('products.*', 'stores.name as store_name')
            ->where('products.store_id', '=', $store_id)
            ->where('products.is_featured', '=', true)
            ->whereRaw("TRIM(products.status)::text = 'ACTIVE'")
            ->whereRaw("TRIM(stores.status)::text = 'ACTIVE'")
            ->where('stores.is_verified', true)
            ->whereRaw("TRIM(products.status)::text <> 'ARCHIVED'")
            ->orderByDesc('products.created_at')
            ->get();

        return response()->json($featured);
    }

    // 🧩 Productos no destacados por tienda (solo activos/verificados)
    public function notFeaturedByStoreFull($store_id)
    {
        $notFeatured = DB::table('products')
            ->join('stores', 'stores.id', '=', 'products.store_id')
            ->select('products.*', 'stores.name as store_name')
            ->where('products.store_id', '=', $store_id)
            ->where('products.is_featured', '=', false)
            ->whereRaw("TRIM(products.status)::text = 'ACTIVE'")
            ->whereRaw("TRIM(stores.status)::text = 'ACTIVE'")
            ->where('stores.is_verified', true)
            ->whereRaw("TRIM(products.status)::text <> 'ARCHIVED'")
            ->orderByDesc('products.created_at')
            ->get();

        return response()->json($notFeatured);
    }
    // 👤 Productos completos de la tienda (solo excluye ARCHIVED)
    public function allByStore($store_id)
    {
        $products = DB::table('products')
            ->join('stores', 'stores.id', '=', 'products.store_id')
            ->select('products.*', 'stores.name as store_name')
            ->where('products.store_id', '=', $store_id)
            ->whereRaw("TRIM(products.status)::text <> 'ARCHIVED'")
            ->orderByDesc('products.created_at')
            ->get();

        return response()->json($products);
    }
    // 💸 Productos en oferta por tienda (público)
    public function offersByStore($store_id)
    {
        $offers = DB::table('products')
            ->join('stores', 'stores.id', '=', 'products.store_id')
            ->select('products.*', 'stores.name as store_name')
            ->where('products.store_id', '=', $store_id)
            ->whereNotNull('products.discount_price') // debe tener descuento
            ->where('products.discount_price', '>', 0)
            ->whereRaw("TRIM(products.status)::text = 'ACTIVE'")
            ->whereRaw("TRIM(products.status)::text <> 'ARCHIVED'")
            ->whereRaw("TRIM(stores.status)::text = 'ACTIVE'")
            ->where('stores.is_verified', true)
            ->orderByDesc('products.created_at')
            ->get();

        return response()->json($offers);
    }
    // 🏆 Productos más vendidos (solo los que tengan al menos 1 venta)
    public function topSelling()
    {
        $products = DB::table('products')
            ->join('stores', 'stores.id', '=', 'products.store_id')
            ->select('products.*', 'stores.name as store_name')
            ->where('products.sold_count', '>=', 1) // ✅ al menos una venta
            ->whereRaw("TRIM(products.status)::text = 'ACTIVE'")
            ->whereRaw("TRIM(stores.status)::text = 'ACTIVE'")
            ->where('stores.is_verified', true)
            ->whereRaw("TRIM(products.status)::text <> 'ARCHIVED'")
            ->orderByDesc('products.sold_count') // ✅ orden de más vendidos
            ->orderByDesc('products.created_at') // (desempate)
            ->limit(20)
            ->get();

        return response()->json($products);
    }
    // 🏆 Productos más vendidos de una tienda específica (solo los que tengan al menos 1 venta)
    public function topSellingByStore($store_id)
    {
        $products = DB::table('products')
            ->join('stores', 'stores.id', '=', 'products.store_id')
            ->select('products.*', 'stores.name as store_name')
            ->where('products.store_id', '=', $store_id)
            ->where('products.sold_count', '>=', 1) // ✅ mínimo una venta
            ->whereRaw("TRIM(products.status)::text = 'ACTIVE'")
            ->whereRaw("TRIM(stores.status)::text = 'ACTIVE'")
            ->where('stores.is_verified', true)
            ->whereRaw("TRIM(products.status)::text <> 'ARCHIVED'")
            ->orderByDesc('products.sold_count') // ✅ más vendidos primero
            ->orderByDesc('products.created_at') // desempate
            ->limit(20)
            ->get();

        return response()->json($products);
    }
    // 🏆 Productos más vendidos por categoría (solo los que tengan al menos 1 venta)
    public function topSellingByCategory($category_id)
    {
        $products = DB::table('products')
            ->join('product_category', 'products.id', '=', 'product_category.product_id')
            ->join('categories', 'categories.id', '=', 'product_category.category_id')
            ->join('stores', 'stores.id', '=', 'products.store_id')
            ->select(
                'products.*',
                'stores.name as store_name',
                'categories.name as category_name'
            )
            ->where('product_category.category_id', '=', $category_id)
            ->where('products.sold_count', '>=', 1) // ✅ al menos 1 venta
            ->whereRaw("TRIM(products.status)::text = 'ACTIVE'")
            ->whereRaw("TRIM(stores.status)::text = 'ACTIVE'")
            ->where('stores.is_verified', true)
            ->whereRaw("TRIM(products.status)::text <> 'ARCHIVED'")
            ->orderByDesc('products.sold_count') // ✅ más vendidos primero
            ->orderByDesc('products.created_at') // desempate por fecha
            ->limit(20)
            ->get();

        return response()->json($products);
    }

}
