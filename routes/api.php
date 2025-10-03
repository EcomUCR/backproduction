<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

// Controllers
use App\Http\Controllers\UserController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductImageController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ContactMessageController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
// NOT FOUND CONTROLLERS
// use App\Http\Controllers\StoreController;
// use App\Http\Controllers\StoreBannerController;
// use App\Http\Controllers\StoreReviewController;
// use App\Http\Controllers\StoreSocialController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
| Rutas que no requieren autenticación
*/

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

// Productos públicos (solo lectura)
Route::get('/products', [ProductController::class, 'index']);              // todos los productos
Route::get('/products/search', [ProductController::class, 'search']);      // buscar
Route::get('/products/{id}', [ProductController::class, 'show']);          // detalle
Route::get('/products/vendor/{vendorId}', [ProductController::class, 'byVendor']); // productos por vendor

// DB Test
Route::get('/db-test', function () {
    try {
        $result = DB::select('select current_database() as db, inet_server_addr() as host');
        return response()->json($result);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

/*
|--------------------------------------------------------------------------
| Protected Routes
|--------------------------------------------------------------------------
| Rutas que requieren token de autenticación con Sanctum
*/
Route::middleware('auth:sanctum')->group(function () {
    // Usuario
    Route::get('/users', [UserController::class, 'listUsers']);
    Route::get('/me', [UserController::class, 'me']);
    Route::post('/logout', [UserController::class, 'logout']);
    //Route::put('/clients/{id}', [ClientController::class, 'update']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/change-password', [UserController::class, 'changePassword']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

    // Perfiles
    Route::get('/profiles/{id}', [ProfileController::class, 'show']);

    // Vendors (CRUD + productos de un vendor)
    //Route::apiResource('vendors', VendorController::class);
    //Route::get('/vendors/{id}/products', [VendorController::class, 'products']);

    // Productos (CRUD completo)
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);

    // Imágenes de producto 
    //Route::get('/products/{id}/images', [ProductImageController::class, 'index']);        
    //Route::post('/product-images', [ProductImageController::class, 'store']);             
    //Route::delete('/product-images/{id}', [ProductImageController::class, 'destroy']);   
    
    // Ordenes (CRUD)
    Route::apiResource('orders', OrderController::class);

    // Mensajes de contacto (CRUD)
    Route::apiResource('contact-messages', ContactMessageController::class);

    // Sesiones autenticadas
    Route::post('/session', [AuthenticatedSessionController::class, 'store']);
    Route::delete('/session', [AuthenticatedSessionController::class, 'destroy']);
});