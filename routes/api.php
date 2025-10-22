<?php

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\NotificationController;
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

// use App\Http\Controllers\StoreController;
// use App\Http\Controllers\StoreBannerController;
use App\Http\Controllers\StoreReviewController;
// use App\Http\Controllers\StoreSocialController;

use App\Http\Controllers\PaymentController;
use App\Http\Controllers\OrderController;


/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
| Rutas que no requieren autenticación
*/

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
Route::get('/coupons', [CouponController::class, 'index']);
Route::post('/coupons', [CouponController::class, 'store']);
Route::get('/coupons/{id}', [CouponController::class, 'show']);
Route::put('/coupons/{id}', [CouponController::class, 'update']);
Route::delete('/coupons/{id}', [CouponController::class, 'destroy']);
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
Route::get('/products', [ProductController::class, 'index']);              // todos los productos
Route::get('/products/search', [ProductController::class, 'search']);      // buscar
Route::get('/products/featured', [ProductController::class, 'featured']);
Route::get('/products/{id}', [ProductController::class, 'show']);          // detalle
Route::get('/products/vendor/{vendorId}', [ProductController::class, 'byVendor']); // productos por vendor
Route::get('/categories/{id}/products', [ProductController::class, 'byCategory']);
Route::get('/stores/{store_id}/featured', [ProductController::class, 'featuredByStore']);

//Tienda
Route::get('/stores/{user_id}', [StoreController::class, 'show']);
Route::get('/stores/{store_id}/products', [ProductController::class, 'showByStore']);
Route::get('/stores', [StoreController::class, 'index']);
Route::put('/stores/{id}', [StoreController::class, 'update']);
// 👤 Todos los productos (excepto ARCHIVED) visibles para el dueño
Route::get('/store/{store_id}/all', [ProductController::class, 'allByStore']);

// Mensajes de contacto
Route::apiResource('contact-messages', ContactMessageController::class);

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
// ✅ Nuevas rutas específicas por tienda (solo lectura pública)
Route::get('/store/{store_id}/products', [ProductController::class, 'indexByStore']);
Route::get('/store/{store_id}/products/{product_id}', [ProductController::class, 'showByStoreProduct']);
Route::get('/store/{store_id}/featured', [ProductController::class, 'featuredByStore']);
// 💸 Productos en oferta por tienda (público)
Route::get('/store/{store_id}/offers', [ProductController::class, 'offersByStore']);

//Reseñas de tiendas
Route::get('/stores/{store_id}/reviews', [StoreReviewController::class, 'reviewsByStore']); // listar reseñas por tienda
Route::get('/stores/{store_id}/reviews/summary', [StoreReviewController::class, 'summary']);

//Banners
Route::get('/banners', [BannerController::class, 'index']);
Route::get('/banners/{id}', [BannerController::class, 'show']);

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
/*
|--------------------------------------------------------------------------
| Protected Routes
|--------------------------------------------------------------------------
| Rutas que requieren token de autenticación con Sanctum
*/
Route::middleware('auth:sanctum')->group(function () {
    //Notificaciones
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications', [NotificationController::class, 'store']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::patch('/notifications/{id}/archive', [NotificationController::class, 'archive']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
    // Usuario
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/me', [UserController::class, 'me']);
    Route::post('/logout', [UserController::class, 'logout']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/change-password', [UserController::class, 'changePassword']);
    Route::get('/users/{id}/store', [UserController::class, 'getStore']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    Route::patch('/users/{id}', [UserController::class, 'update']);
    Route::put('/users/{id}/status', [UserController::class, 'updateStatus']);

    //Store
    Route::get('/stores/user/{user_id}', [StoreController::class, 'showByUser']);
    Route::patch('/stores/{id}', [StoreController::class, 'update']);
    Route::put('/stores/{id}', [StoreController::class, 'update']);

    //Banners
    Route::post('/banners', [BannerController::class, 'store']);
    Route::put('/banners/{id}', [BannerController::class, 'update']);
    Route::patch('/banners/{id}', [BannerController::class, 'update']);
    Route::delete('/banners/{id}', [BannerController::class, 'destroy']);
    //Cart
    Route::get('/cart/me', [CartController::class, 'me']);
    Route::post('/cart/clear', [CartController::class, 'clear']);
    Route::post('/cart/add', [CartController::class, 'addItem']);
    Route::patch('/cart/item/{id}', [CartController::class, 'updateItem']);
    Route::delete('/cart/item/{id}', [CartController::class, 'removeItem']);


    Route::post('/cart/items', [CartItemController::class, 'add']);
    Route::patch('/cart/items/{item}', [CartItemController::class, 'updateQuantity']);
    Route::delete('/cart/items/{item}', [CartItemController::class, 'destroy']);
    Route::middleware('auth:sanctum')->get('/cart/totals', [CartController::class, 'totals']);

    //ordenes desde el checkout
    Route::post('/checkout', [CheckoutController::class, 'checkout']);

    // 💳 Pagos Stripe (Checkout crea la orden con productos)
    Route::post('/create-payment-intent', [PaymentController::class, 'createPaymentIntent']);

    // (Opcionales para administración)
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::put('/orders/{id}', [OrderController::class, 'update']);
    Route::delete('/orders/{id}', [OrderController::class, 'destroy']);
    Route::get('/user/{userId}/orders', [OrderController::class, 'userOrders']);

    // Reseñas de tiendas
    Route::post('/store-reviews', [StoreReviewController::class, 'store']); // crear reseña

    // Perfiles
    Route::get('/profiles/{id}', [ProfileController::class, 'show']);

    // Productos (CRUD completo)
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);


    // Ordenes (CRUD)
    Route::apiResource('orders', OrderController::class);


    // Sesiones autenticadas
    Route::post('/session', [AuthenticatedSessionController::class, 'store']);
    Route::delete('/session', [AuthenticatedSessionController::class, 'destroy']);
});