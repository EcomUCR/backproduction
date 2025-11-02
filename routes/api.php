<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\BannerImageController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Controllers
use App\Http\Controllers\UserController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductImageController;
use App\Http\Controllers\ContactMessageController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ImageUploadController;
use App\Http\Controllers\OpenAIController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CartItemController;
use App\Http\Controllers\BannerController;
use App\Services\VisaClient;
use App\Services\Contracts\VisaClientContract;
use App\Http\Controllers\PageBannerController;

// use App\Http\Controllers\StoreController;
// use App\Http\Controllers\StoreBannerController;
use App\Http\Controllers\StoreReviewController;
// use App\Http\Controllers\StoreSocialController;

use App\Http\Controllers\PaymentController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\CheckoutItemController;

use App\Http\Controllers\WishlistItemController;
use App\Http\Controllers\WishlistController;


/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
| Rutas que no requieren autenticación
*/
//Reportes
Route::post('/reports', [ReportController::class, 'store']);

//OpenAI API
Route::post('/openai/description', [OpenAIController::class, 'generateDescription'])
    ->middleware('throttle:10,1'); // opcional: 10 peticiones por minuto por IP

//Imagenes
Route::post('/upload-image', [ImageUploadController::class, 'upload']);
Route::get('/test-env', function () {
    return [
        'env' => env('CLOUDINARY_URL'),
        'config' => config('cloudinary.cloud_url'),
    ];
});
Route::get('/debug-cloudinary', function () {
    return [
        'config_raw' => config('cloudinary'),
        'cloud_url' => config('cloudinary.cloud_url'),
        'env' => env('CLOUDINARY_URL')
    ];
});

//Cupones
Route::post('/coupons/validate', [CouponController::class, 'validateCoupon']);

// Registro y login
Route::post('/users', [UserController::class, 'store']);
Route::post('/login', [UserController::class, 'login']);

// Categorías
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);

// Password reset
Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('guest');
Route::post('/reset-password', [NewPasswordController::class, 'store'])
    ->middleware('guest')
    ->name('password.update');

//FOrmulario de contacto
Route::post('/contact-messages', [ContactMessageController::class, 'store']);

// Productos públicos (solo lectura)
Route::get('/products', action: [ProductController::class, 'index']);              // todos los productos
Route::get('/products/search', [ProductController::class, 'search']);      // buscar
Route::get('/products/featured', [ProductController::class, 'featured']);
Route::get('/products/offers', [ProductController::class, 'getOffers']); // productos por vendor
Route::get('/products/{id}', [ProductController::class, 'show']);          // detalle
Route::get('/products/vendor/{vendorId}', [ProductController::class, 'byVendor']); // productos por vendor
Route::get('/categories/{id}/products', [ProductController::class, 'byCategory']);
Route::get('/stores/{store_id}/featured', [ProductController::class, 'featuredByStore']);

//Tienda
Route::get('/stores/{user_id}', [StoreController::class, 'show']);
Route::get('/stores/{store_id}/products', [ProductController::class, 'showByStore']);
Route::get('/stores', [StoreController::class, 'index']);
Route::put('/stores/{id}', [StoreController::class, 'update']);
Route::get('/store/{store_id}/search', [ProductController::class, 'searchByStore']);
Route::get('/store/{id}/rating', [StoreController::class, 'getRating']);

// 👤 Todos los productos (excepto ARCHIVED) visibles para el dueño
Route::get('/store/{store_id}/all', [ProductController::class, 'allByStore']);

// Mensajes de contacto
Route::post('/contact-messages', [ContactMessageController::class, 'store']);

// DB Test
Route::get('/db-test', function () {
    try {
        $result = DB::select('select current_database() as db, inet_server_addr() as host');
        return response()->json($result);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});
Route::get('/my-ip', function () {
    $ip = Http::get('https://ifconfig.me')->body();
    return response()->json(['ip' => $ip]);
});

// Nuevas rutas específicas por tienda (solo lectura pública)
Route::get('/store/{store_id}/products', [ProductController::class, 'indexByStore']);
Route::get('/store/{store_id}/products/{product_id}', [ProductController::class, 'showByStoreProduct']);
Route::get('/store/{store_id}/featured', [ProductController::class, 'featuredByStore']);

// Productos en oferta por tienda (público)
Route::get('/store/{store_id}/offers', [ProductController::class, 'offersByStore']);

//Reseñas de tiendas
Route::get('/stores/{store_id}/reviews', [StoreReviewController::class, 'reviewsByStore']); // listar reseñas por tienda
Route::get('/stores/{store_id}/reviews/summary', [StoreReviewController::class, 'summary']);

//Banners
Route::get('/banners', [BannerController::class, 'index']);
Route::get('/banners/{id}', [BannerController::class, 'show']);
Route::get('/banner-images', [BannerImageController::class, 'index']);
// Page Banners
Route::get('/page-banners', [PageBannerController::class, 'index']);
Route::get('/page-banners/{id}', [PageBannerController::class, 'show']);

// Wishlist pública
Route::get('/wishlist/public/{slug}', [WishlistController::class, 'showPublic']);

//API VISA
Route::get('/visa/test', function (VisaClientContract $visa) {
    try {
        $response = $visa->makeRequest('/forexrates/v1/foreignexchangerates', [
            'destinationCurrencyCode' => 'USD',
            'sourceCurrencyCode' => 'CRC',
        ]);

        return response()->json($response->json(), $response->status());
    } catch (\Throwable $e) {
        Log::error('❌ VISA ERROR: ' . $e->getMessage());
        return response()->json([
            'error' => true,
            'message' => $e->getMessage(),
        ], 500);
    }
});

// Carrito - Totales - público
Route::post('/cart/calculateProductTotal', [CartController::class, 'calculateProductTotal']);

/*
|--------------------------------------------------------------------------
| Protected Routes
|--------------------------------------------------------------------------
| Rutas que requieren token de autenticación con Sanctum
*/
Route::middleware('auth:sanctum')->group(function () {
    //Adresses
    Route::get('/addresses', [AddressController::class, 'index']);
    Route::post('/addresses', [AddressController::class, 'store']);
    Route::patch('/addresses/{id}', [AddressController::class, 'update']);
    Route::delete('/addresses/{id}', [AddressController::class, 'destroy']);
    Route::get('/user/addresses', [AddressController::class, 'userAddresses']);

    // 📩 Notificaciones
    Route::post('/contact-messages/{id}/reply', [ContactMessageController::class, 'reply']);
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications', [NotificationController::class, 'store']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::patch('/notifications/{id}/archive', [NotificationController::class, 'archive']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
    Route::get('/contact-messages', [ContactMessageController::class, 'index']);

    // 🗑️ Eliminar un mensaje (solo admin)
    Route::delete('/contact-messages/{id}', [ContactMessageController::class, 'destroy']);

    // 💬 Responder mensaje directamente desde el buzón
    Route::post('/contact-messages/{id}/reply', [ContactMessageController::class, 'reply']);

    // 👤 Usuario
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/me', [UserController::class, 'me']);
    Route::post('/logout', [UserController::class, 'logout']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/change-password', [UserController::class, 'changePassword']);
    Route::get('/users/{id}/store', [UserController::class, 'getStore']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    Route::patch('/users/{id}', [UserController::class, 'update']);
    Route::patch('admin/users/{id}', [UserController::class, 'adminUpdate']);

    Route::put('/users/{id}/status', [UserController::class, 'updateStatus']);


    // 🏬 Tiendas
    Route::get('/stores/user/{user_id}', [StoreController::class, 'showByUser']);
    Route::patch('/stores/{id}', [StoreController::class, 'update']);
    Route::patch('admin/stores/{id}', [StoreController::class, 'adminUpdate']);

    Route::put('/stores/{id}', [StoreController::class, 'update']);

    // 🎨 Banners
    Route::post('/banners', [BannerController::class, 'store']);
    Route::put('/banners/{id}', [BannerController::class, 'update']);
    Route::patch('/banners/{id}', [BannerController::class, 'update']);
    Route::delete('/banners/{id}', [BannerController::class, 'destroy']);
    Route::post('/banner-images', [BannerImageController::class, 'store']);   // crear nuevo banner
    Route::delete('/banner-images/{id}', [BannerImageController::class, 'destroy']);
    // 🎨 Page Banners
    Route::post('/page-banners', [PageBannerController::class, 'store']);
    Route::put('/page-banners/{id}', [PageBannerController::class, 'update']);
    Route::patch('/page-banners/{id}', [PageBannerController::class, 'update']);
    Route::delete('/page-banners/{id}', [PageBannerController::class, 'destroy']);

    // 🛒 Carrito
    Route::get('/cart/me', [CartController::class, 'me']);
    Route::post('/cart/clear', [CartController::class, 'clear']);
    Route::post('/cart/add', [CartController::class, 'addItem']);
    Route::patch('/cart/item/{id}', [CartController::class, 'updateItem']);
    Route::delete('/cart/item/{id}', [CartController::class, 'removeItem']);
    Route::post('/cart/items', [CartItemController::class, 'add']);
    Route::patch('/cart/items/{item}', [CartItemController::class, 'updateQuantity']);
    Route::delete('/cart/items/{item}', [CartItemController::class, 'destroy']);
    Route::get('/cart/totals', [CartController::class, 'totals']);
    

    // 💳 NUEVO Checkout modular
    Route::post('/checkout/init', [CheckoutController::class, 'init']); // crea orden vacía
    Route::post('/checkout/items', [CheckoutItemController::class, 'store']); // agrega productos
    Route::post('/checkout/confirm', [CheckoutController::class, 'confirm']); // confirma el pago

    // ✅ Checkout: productos de la orden
    Route::post('/checkout/items', [CheckoutItemController::class, 'store']);
    Route::get('/checkout/{orderId}/items', [CheckoutItemController::class, 'index']);

    // 💸 Stripe (crea PaymentIntent)
    Route::post('/create-payment-intent', [PaymentController::class, 'createPaymentIntent']);

    //wishlist
    Route::get('/wishlist/me', [WishlistController::class, 'me']);
    Route::post('/wishlist/toggle-visibility', [WishlistController::class, 'toggleVisibility']);
    Route::post('/wishlist/clear', [WishlistController::class, 'clear']);
    Route::post('/wishlist/add', [WishlistItemController::class, 'addItem']);
    Route::delete('/wishlist/remove/{id}', [WishlistItemController::class, 'removeItem']);

    // 📦 Órdenes
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::put('/orders/{id}', [OrderController::class, 'update']);
    Route::delete('/orders/{id}', [OrderController::class, 'destroy']);
    Route::get('/user/{userId}/orders', [OrderController::class, 'userOrders']);
    Route::get('/stores/{storeId}/orders', [OrderController::class, 'storeOrders']);

    // 🌟 Reseñas de tiendas
    Route::post('/store-reviews', [StoreReviewController::class, 'store']);

    // 👤 Perfiles
    Route::get('/profiles/{id}', [ProfileController::class, 'show']);

    // 🧩 Productos
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);

    // 🧾 CRUD de órdenes (admin)
    Route::apiResource('orders', OrderController::class);

    // 🔐 Sesiones autenticadas
    Route::post('/session', [AuthenticatedSessionController::class, 'store']);
    Route::delete('/session', [AuthenticatedSessionController::class, 'destroy']);

    //Reportes
    Route::get('/reports', [ReportController::class, 'index']);     // listar todos
    Route::get('/reports/{id}', [ReportController::class, 'show']); // ver detalle
    Route::put('/reports/{id}', [ReportController::class, 'update']); // actualizar estado / notas
    Route::delete('/reports/{id}', [ReportController::class, 'destroy']); // eliminar

    //Cupones
    Route::get('/coupons', [CouponController::class, 'index']);
Route::post('/coupons', [CouponController::class, 'store']);
Route::get('/coupons/{id}', [CouponController::class, 'show']);
Route::put('/coupons/{id}', [CouponController::class, 'update']);
Route::delete('/coupons/{id}', [CouponController::class, 'destroy']);
});

