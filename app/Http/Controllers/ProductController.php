<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    // Retrieve all active products from active and verified stores.
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 30);

        $products = DB::table('products')
            ->join('stores', 'stores.id', '=', 'products.store_id')
            ->select('products.*', 'stores.name as store_name')
            ->whereRaw("TRIM(products.status) = 'ACTIVE'")
            ->whereRaw("TRIM(stores.status) = 'ACTIVE'")
            ->where('stores.is_verified', true)
            ->whereRaw("TRIM(products.status) <> 'ARCHIVED'")
            ->orderByDesc('products.created_at')
            ->paginate($perPage);

        // Esto devuelve estructura JSON con meta y links automáticos:
        // { data: [...], current_page: 1, last_page: 5, total: 150, per_page: 30 }
        return response()->json($products);
    }


    // Retrieve a specific product if its store is active and product is not archived.
    public function show($id)
    {
        $product = DB::table('products')
            ->join('stores', 'stores.id', '=', 'products.store_id')
            ->select('products.*', 'stores.name as store_name')
            ->where('products.id', '=', $id)
            ->whereRaw("TRIM(products.status) <> 'ARCHIVED'")
            ->whereRaw("TRIM(stores.status) = 'ACTIVE'")
            ->first();

        if (!$product) {
            return response()->json(['message' => 'Producto no encontrado o la tienda está inactiva'], 404);
        }

        $categories = DB::table('categories')
            ->join('product_category', 'categories.id', '=', 'product_category.category_id')
            ->where('product_category.product_id', '=', $id)
            ->select('categories.id', 'categories.name')
            ->get();

        $product->categories = $categories;

        return response()->json($product);
    }
    // Retrieve all discounted products (offers) across verified and active stores.
    public function getOffers()
    {
        try {
            $products = DB::table('products')
                ->join('stores', 'stores.id', '=', 'products.store_id')
                ->select(
                    'products.id',
                    'products.name',
                    'products.price',
                    'products.discount_price',
                    'products.image_1_url',
                    'products.image_2_url',
                    'products.image_3_url',
                    'products.status',
                    'products.is_featured',
                    'products.created_at',
                    'stores.name as store_name'
                )
                // 🧩 Solo productos con descuento válido
                ->whereNotNull('products.price')
                ->whereNotNull('products.discount_price')
                ->where('products.discount_price', '>', 0)
                ->whereRaw('products.discount_price < products.price')

                // 🏪 Solo productos y tiendas activas y verificadas
                ->where('products.status', 'ACTIVE')
                ->where('stores.status', 'ACTIVE')
                ->where('stores.is_verified', true)

                // 📉 Calcular el % de descuento real
                // (1 - discount_price / price) * 100
                ->selectRaw('(100 * (1 - (COALESCE(products.discount_price, 0) / NULLIF(products.price, 0)))) AS discount_percent')

                // 🔢 Ordenar por % de descuento (mayor primero), luego por fecha
                ->orderByDesc('discount_percent')
                ->orderByDesc('products.created_at')

                ->limit(50)
                ->get();

            // 🔀 Mezclar un poco (opcional)
            $seed = intval(date('z'));
            srand($seed);
            $shuffled = $products->shuffle();

            return response()->json($shuffled->values());
        } catch (\Throwable $e) {
            \Log::error('❌ getOffers() failed', [
                'msg' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            return response()->json([
                'error' => true,
                'message' => 'Error obteniendo ofertas: ' . $e->getMessage(),
            ], 500);
        }
    }

    // Retrieve featured products from active and verified stores.
    public function featured()
    {
        $featured = DB::table('products')
            ->join('stores', 'stores.id', '=', 'products.store_id')
            ->select('products.*', 'stores.name as store_name')
            ->where('products.is_featured', true)
            ->whereRaw("TRIM(products.status) = 'ACTIVE'")
            ->whereRaw("TRIM(stores.status) = 'ACTIVE'")
            ->whereRaw("TRIM(products.status) <> 'ARCHIVED'")
            ->where('stores.is_verified', true)
            ->get();

        // 🔹 Generar semilla según día del año (0–365)
        $seed = intval(date('z'));
        srand($seed);

        // 🔀 Barajar determinísticamente
        $shuffled = $featured->shuffle();

        // 🔢 Mostrar máximo 20 (por ejemplo)
        return response()->json($shuffled->take(20)->values());
    }


    // Retrieve non-featured products from active and verified stores.
    public function notFeatured()
    {
        $notFeatured = DB::table('products')
            ->join('stores', 'stores.id', '=', 'products.store_id')
            ->select('products.*', 'stores.name as store_name')
            ->where('products.is_featured', false)
            ->where('products.status', 'ACTIVE')
            ->where('stores.status', 'ACTIVE')
            ->where('products.status', '<>', 'ARCHIVED')
            ->limit(10)
            ->get();

        return response()->json($notFeatured);
    }

    // Retrieve all active products for a specific verified store.
    public function showByStore($store_id)
    {
        $products = DB::table('products')
            ->join('stores', 'stores.id', '=', 'products.store_id')
            ->select('products.*', 'stores.name as store_name')
            ->where('products.store_id', $store_id)
            ->where('products.status', 'ACTIVE')
            ->where('stores.status', 'ACTIVE')
            ->where('stores.is_verified', true)
            ->where('products.status', '<>', 'ARCHIVED')
            ->orderByDesc('products.created_at')
            ->get();

        return response()->json($products);
    }


    // Retrieve all active products in a specific category from active stores.
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
            ->where('product_category.category_id', $category_id)
            ->where('products.status', 'ACTIVE')
            ->where('stores.status', 'ACTIVE')
            ->where('products.status', '<>', 'ARCHIVED')
            ->get();

        return response()->json($products);
    }


    // Retrieve featured products for a specific verified store.
    public function featuredByStore($store_id)
    {
        $featured = DB::table('products')
            ->join('stores', 'stores.id', '=', 'products.store_id')
            ->select('products.*', 'stores.name as store_name')
            ->where('products.store_id', $store_id)
            ->where('products.is_featured', true)
            ->where('products.status', 'ACTIVE')
            ->where('stores.status', 'ACTIVE')
            ->where('products.status', '<>', 'ARCHIVED')
            ->where('stores.is_verified', true)
            ->get();

        return response()->json($featured);
    }


    // Create a new product and assign categories if provided.
    public function store(Request $request)
    {
        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'sku' => 'required|string|max:100|unique:products,sku',
            'name' => 'required|string|max:80',
            'image_1_url' => 'required|string|max:255',
            'image_2_url' => 'nullable|string|max:255',
            'image_3_url' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'details' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'sold_count' => 'nullable|integer|min:0',
            'status' => 'nullable|string|in:ACTIVE,INACTIVE,ARCHIVED,DRAFT',
            'is_featured' => 'nullable|boolean',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        // 🧩 Inserta el producto y obtiene su ID
        $productId = DB::table('products')->insertGetId([
            'store_id' => $validated['store_id'],
            'sku' => $validated['sku'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'details' => $validated['details'] ?? null,
            'price' => $validated['price'],
            'discount_price' => $validated['discount_price'] ?? null,
            'stock' => $validated['stock'] ?? 0,
            'sold_count' => $validated['sold_count'] ?? 0,
            'status' => $validated['status'] ?? 'ACTIVE',
            'is_featured' => $validated['is_featured'] ?? false,
            'image_1_url' => $validated['image_1_url'],
            'image_2_url' => $validated['image_2_url'] ?? null,
            'image_3_url' => $validated['image_3_url'] ?? null,
            'rating' => 0.0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 🔗 Vincula categorías si se enviaron
        if (!empty($validated['category_ids'])) {
            $relations = [];
            foreach ($validated['category_ids'] as $categoryId) {
                $relations[] = [
                    'product_id' => $productId,
                    'category_id' => $categoryId,
                ];
            }
            DB::table('product_category')->insert($relations);
        }

        // ✅ Devuelve el producto recién creado
        $product = DB::table('products')->where('id', $productId)->first();

        return response()->json($product, 201);
    }


    // Update a product and synchronize its categories if provided.
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'sku' => 'sometimes|string|max:100|unique:products,sku,' . $id,
            'name' => 'sometimes|string|max:80',
            'description' => 'nullable|string',
            'details' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'status' => 'sometimes|string|in:ACTIVE,INACTIVE,ARCHIVED,DRAFT',
            'is_featured' => 'sometimes|boolean',
            'image_1_url' => 'nullable|string|max:255',
            'image_2_url' => 'nullable|string|max:255',
            'image_3_url' => 'nullable|string|max:255',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        // 🧩 Definimos los campos que se pueden actualizar
        $allowedFields = [
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

        // 🧱 Solo agrega al arreglo los campos presentes en la request
        foreach ($allowedFields as $field) {
            if ($request->filled($field) || $request->has($field)) {
                $updateData[$field] = $request->input($field);
            }
        }

        // 🕒 Actualiza la marca de tiempo
        $updateData['updated_at'] = now();

        // ⚙️ Ejecuta la actualización
        DB::table('products')
            ->where('id', $id)
            ->update($updateData);

        // 🔗 Actualiza categorías si vienen en el request
        if ($request->has('category_ids')) {
            DB::table('product_category')
                ->where('product_id', $id)
                ->delete();

            if (!empty($validated['category_ids'])) {
                $relations = [];
                foreach ($validated['category_ids'] as $categoryId) {
                    $relations[] = [
                        'product_id' => $id,
                        'category_id' => $categoryId,
                    ];
                }
                DB::table('product_category')->insert($relations);
            }
        }

        // ✅ Devuelve el producto actualizado
        $updatedProduct = DB::table('products')
            ->where('id', $id)
            ->first();

        return response()->json($updatedProduct);
    }




    // Delete a product by its ID.
    public function destroy($id)
    {
        DB::table('products')->where('id', '=', $id)->delete();
        return response()->json(null, 204);
    }

    // Retrieve all active products for a specific verified store.
    public function indexByStore($store_id)
    {
        $products = DB::table('products')
            ->join('stores', 'stores.id', '=', 'products.store_id')
            ->select('products.*', 'stores.name as store_name')
            ->where('products.store_id', $store_id)
            ->whereRaw("TRIM(products.status) = 'ACTIVE'")
            ->whereRaw("TRIM(stores.status) = 'ACTIVE'")
            ->whereRaw("TRIM(products.status) <> 'ARCHIVED'")
            ->where('stores.is_verified', true)
            ->orderByDesc('products.created_at')
            ->get();

        return response()->json($products);
    }


    // Retrieve a specific product from a specific verified store.
    public function showByStoreProduct($store_id, $product_id)
    {
        $product = DB::table('products')
            ->join('stores', 'stores.id', '=', 'products.store_id')
            ->select('products.*', 'stores.name as store_name')
            ->where('products.store_id', $store_id)
            ->where('products.id', $product_id)
            ->whereRaw("TRIM(products.status) = 'ACTIVE'")
            ->whereRaw("TRIM(stores.status) = 'ACTIVE'")
            ->whereRaw("TRIM(products.status) <> 'ARCHIVED'")
            ->where('stores.is_verified', true)
            ->first();

        if (!$product) {
            return response()->json(['message' => 'Producto no encontrado o no disponible'], 404);
        }

        return response()->json($product);
    }


    // Retrieve featured products from a specific verified store.
    public function featuredByStoreFull($store_id)
    {
        $featured = DB::table('products')
            ->join('stores', 'stores.id', '=', 'products.store_id')
            ->select('products.*', 'stores.name as store_name')
            ->where('products.store_id', $store_id)
            ->where('products.is_featured', true)
            ->whereRaw("TRIM(products.status) = 'ACTIVE'")
            ->whereRaw("TRIM(stores.status) = 'ACTIVE'")
            ->whereRaw("TRIM(products.status) <> 'ARCHIVED'")
            ->where('stores.is_verified', true)
            ->orderByDesc('products.created_at')
            ->get();

        return response()->json($featured);
    }


    // Retrieve non-featured products from a specific verified store.
    public function notFeaturedByStoreFull($store_id)
    {
        $notFeatured = DB::table('products')
            ->join('stores', 'stores.id', '=', 'products.store_id')
            ->select('products.*', 'stores.name as store_name')
            ->where('products.store_id', $store_id)
            ->where('products.is_featured', false)
            ->whereRaw("TRIM(products.status) = 'ACTIVE'")
            ->whereRaw("TRIM(stores.status) = 'ACTIVE'")
            ->whereRaw("TRIM(products.status) <> 'ARCHIVED'")
            ->where('stores.is_verified', true)
            ->orderByDesc('products.created_at')
            ->get();

        return response()->json($notFeatured);
    }


    // Retrieve all products for a store excluding archived.
    public function allByStore($store_id)
    {
        $products = DB::table('products')
            ->join('stores', 'stores.id', '=', 'products.store_id')
            ->select('products.*', 'stores.name as store_name')
            ->where('products.store_id', $store_id)
            ->whereRaw("TRIM(products.status) <> 'ARCHIVED'")
            ->orderByDesc('products.created_at')
            ->get();

        return response()->json($products);
    }


    // Retrieve discounted products for a specific verified store.
    public function offersByStore($store_id)
    {
        $offers = DB::table('products')
            ->join('stores', 'stores.id', '=', 'products.store_id')
            ->select('products.*', 'stores.name as store_name')
            ->where('products.store_id', $store_id)
            ->whereNotNull('products.discount_price')
            ->where('products.discount_price', '>', 0)
            ->whereRaw("TRIM(products.status) = 'ACTIVE'")
            ->whereRaw("TRIM(products.status) <> 'ARCHIVED'")
            ->whereRaw("TRIM(stores.status) = 'ACTIVE'")
            ->where('stores.is_verified', true)
            ->orderByDesc('products.created_at')
            ->get();

        return response()->json($offers);
    }


    // Retrieve top-selling products across all stores.
    public function topSelling()
    {
        $products = DB::table('products')
            ->join('stores', 'stores.id', '=', 'products.store_id')
            ->select('products.*', 'stores.name as store_name')
            ->where('products.sold_count', '>=', 1)
            ->whereRaw("TRIM(products.status) = 'ACTIVE'")
            ->whereRaw("TRIM(stores.status) = 'ACTIVE'")
            ->whereRaw("TRIM(products.status) <> 'ARCHIVED'")
            ->where('stores.is_verified', true)
            ->orderByDesc('products.sold_count')
            ->orderByDesc('products.created_at')
            ->limit(20)
            ->get();

        return response()->json($products);
    }


    // Retrieve top-selling products for a specific store.
    public function topSellingByStore($store_id)
    {
        $products = DB::table('products')
            ->join('stores', 'stores.id', '=', 'products.store_id')
            ->select('products.*', 'stores.name as store_name')
            ->where('products.store_id', $store_id)
            ->where('products.sold_count', '>=', 1)
            ->whereRaw("TRIM(products.status) = 'ACTIVE'")
            ->whereRaw("TRIM(stores.status) = 'ACTIVE'")
            ->whereRaw("TRIM(products.status) <> 'ARCHIVED'")
            ->where('stores.is_verified', true)
            ->orderByDesc('products.sold_count')
            ->orderByDesc('products.created_at')
            ->limit(20)
            ->get();

        return response()->json($products);
    }


    // Search for products by name within a specific verified store.
    public function searchByStore(Request $request, $store_id)
    {
        $query = trim($request->input('q', ''));

        if (empty($query)) {
            return response()->json([]);
        }

        $products = DB::table('products')
            ->join('stores', 'stores.id', '=', 'products.store_id')
            ->select('products.*', 'stores.name as store_name')
            ->where('products.store_id', $store_id)
            ->whereRaw("TRIM(products.status) = 'ACTIVE'")
            ->whereRaw("TRIM(stores.status) = 'ACTIVE'")
            ->whereRaw("TRIM(products.status) <> 'ARCHIVED'")
            ->where('stores.is_verified', true)
            ->where(function ($q) use ($query) {
                // ✅ Compatibilidad: PostgreSQL usa ILIKE, MySQL usa LIKE insensible por defecto
                $q->where('products.name', 'LIKE', "%{$query}%");
            })
            ->orderByDesc('products.created_at')
            ->get();

        return response()->json($products);
    }


    // Retrieve top-selling products in a specific category.
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
            ->where('product_category.category_id', $category_id)
            ->where('products.sold_count', '>=', 1)
            ->whereRaw("TRIM(products.status) = 'ACTIVE'")
            ->whereRaw("TRIM(stores.status) = 'ACTIVE'")
            ->whereRaw("TRIM(products.status) <> 'ARCHIVED'")
            ->where('stores.is_verified', true)
            ->orderByDesc('products.sold_count')
            ->orderByDesc('products.created_at')
            ->limit(20)
            ->get();

        return response()->json($products);
    }
}
