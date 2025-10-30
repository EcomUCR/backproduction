<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\BrevoMailer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\Cart;

class UserController extends Controller
{
    // List all users.
    public function index()
    {
        $users = User::with([
            'store' => function ($query) {
                $query->select('id', 'user_id', 'name', 'category_id', 'image', 'banner', 'slug')
                    ->with(['storeSocials', 'banners']);
            },
        ])->get(['id', 'username', 'email', 'first_name', 'last_name', 'image', 'status', 'phone_number', 'role', 'created_at', 'updated_at']);

        return response()->json($users);
    }

    // Show authenticated user with related data.
    public function me(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'No se encontró un usuario autenticado.'
            ], 401);
        }

        $user->load([
            'store:id,user_id,name,slug,description,image,banner,registered_address,support_phone,support_email,status,is_verified'
        ]);

        return response()->json($user);
    }

    // Get authenticated user's addresses.
    public function userAddresses(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'error' => 'Usuario no autenticado'
            ], 401);
        }

        $addresses = $user->addresses()
            ->select('id', 'street', 'city', 'state', 'zip_code', 'country', 'phone_number', 'is_default')
            ->orderByDesc('is_default')
            ->get();

        return response()->json([
            'success' => true,
            'addresses' => $addresses
        ]);
    }

    // Show a user by ID with store relation.
    public function show($id)
    {
        $user = User::with('store:id,user_id,name,slug,status')->findOrFail($id);
        return response()->json($user);
    }

    // Create a new user, cart, and store (if seller), and notify admins.
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'username' => 'required|string|max:100|unique:users',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|min:6',
            'first_name' => 'nullable|string|max:80',
            'last_name' => 'nullable|string|max:80',
            'phone_number' => 'nullable|string|max:20',
            'role' => 'required|string|in:ADMIN,SELLER,CUSTOMER',
        ]);

        $validatedData['password'] = Hash::make($validatedData['password']);

        try {
            $user = User::create($validatedData);
            $cart = Cart::create(['user_id' => $user->id]);
            $user->setRelation('cart', $cart);

            // 📨 Notificación y correo de bienvenida
            try {
                Notification::create([
                    'user_id' => $user->id,
                    'role' => $user->role,
                    'type' => 'WELCOME_USER',
                    'title' => '👋 ¡Bienvenido a TukiShop!',
                    'message' => "Hola {$user->username}, gracias por registrarte en TukiShop. 🎉",
                    'related_id' => $user->id,
                    'related_type' => 'user',
                    'priority' => 'NORMAL',
                    'is_read' => false,
                    'data' => [
                        'user_id' => $user->id,
                        'username' => $user->username,
                        'role' => $user->role,
                    ],
                ]);

                $subject = '🎉 ¡Bienvenido a TukiShop!';
                $body = view('emails.welcome-user-html', [
                    'name' => trim($user->first_name . ' ' . $user->last_name) ?: $user->username,
                    'role' => $user->role,
                    'login_url' => env('DASHBOARD_URL', 'https://tukishop.vercel.app/loginRegister'),
                ])->render();

                BrevoMailer::send($user->email, $subject, $body);
            } catch (\Exception $e) {
                \Log::error('❌ Error al enviar correo/notificación de bienvenida: ' . $e->getMessage());
            }

            if ($user->role === 'SELLER') {
                $store = $user->store()->create([
                    'name' => $user->username,
                    'slug' => Str::slug($user->username) . '-' . $user->id,
                    'category_id' => 1,
                    'status' => 'ACTIVE',
                ]);

                $user->setRelation('store', $store);

                $admins = User::where('role', 'ADMIN')->get();

                foreach ($admins as $admin) {
                    Notification::create([
                        'user_id' => $admin->id,
                        'role' => 'ADMIN',
                        'type' => 'STORE_VERIFICATION',
                        'title' => 'Nueva tienda pendiente de verificación 🏪',
                        'message' => "La tienda '{$store->name}' requiere revisión y verificación.",
                        'related_id' => $store->id,
                        'related_type' => 'store',
                        'priority' => 'HIGH',
                        'is_read' => false,
                        'data' => [
                            'store_id' => $store->id,
                            'store_name' => $store->name,
                            'user_id' => $store->user_id,
                        ],
                    ]);
                }

                $admins = User::where('role', 'ADMIN')->get(['email']);

                if ($admins->isNotEmpty()) {
                    $subject = 'Nueva solicitud de verificación de tienda';
                    $body = view('emails.verification-request-html', [
                        'store_name' => $store->name,
                        'owner_name' => trim($user->first_name . ' ' . $user->last_name) ?: $user->username,
                        'owner_email' => $user->email,
                        'owner_phone' => $user->phone_number ?? 'No especificado',
                        'request_date' => now()->format('d/m/Y H:i'),
                        'admin_url' => env('ADMIN_PANEL_URL', 'https://tukishopcr.com/admin/stores'),
                    ])->render();

                    foreach ($admins as $admin) {
                        BrevoMailer::send($admin->email, $subject, $body);
                    }
                }
            }

            return response()->json([
                'message' => 'Usuario creado correctamente',
                'user' => $user
            ], 201);

        } catch (\Exception $e) {
            \Log::error('❌ Error en registro de usuario: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al crear el usuario o la tienda',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    // ✅ Authenticate user by email OR username (case-insensitive, con logs)
public function login(Request $request)
{
    $credentials = $request->validate([
        'login' => 'required|string|max:100',
        'password' => 'required|string|min:6',
    ]);

    $loginType = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

    // 🔍 Búsqueda insensible a mayúsculas
    $user = User::whereRaw("LOWER($loginType) = LOWER(?)", [$credentials['login']])->first();

    // 🧩 Log para Render (ayuda a depurar fácilmente)
    \Log::info('🧩 Intento de login', [
        'login_enviado' => $credentials['login'],
        'tipo_login' => $loginType,
        'usuario_encontrado' => $user ? $user->id : 'no encontrado',
    ]);

    if (!$user || !Hash::check($credentials['password'], $user->password)) {
        \Log::warning('⚠️ Login fallido', [
            'usuario' => $credentials['login'],
            'razon' => !$user ? 'Usuario no encontrado' : 'Contraseña incorrecta',
        ]);

        return response()->json(['error' => 'Credenciales inválidas'], 401);
    }

    $token = $user->createToken('auth_token')->plainTextToken;

    \Log::info('✅ Login exitoso', [
        'user_id' => $user->id,
        'username' => $user->username,
    ]);

    return response()->json([
        'user' => $user,
        'token' => $token,
    ]);
}


    // Get store associated with user.
    public function getStore($id)
    {
        $user = User::with([
            'store' => function ($query) {
                $query->select(
                    'id',
                    'user_id',
                    'name',
                    'slug',
                    'description',
                    'category_id',
                    'status',
                    'is_verified',
                    'banner',
                    'image',
                    'support_email',
                    'support_phone',
                    'registered_address'
                )->with(['products:id,store_id,name,price,image_1_url']);
            }
        ])->findOrFail($id);

        if (!$user->store) {
            return response()->json(['message' => 'El usuario no tiene una tienda asociada'], 404);
        }

        return response()->json([
            'message' => 'Tienda encontrada correctamente',
            'store' => $user->store,
        ]);
    }

    // Change password for authenticated user.
    public function changePassword(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Usuario no autenticado.'], 401);
        }

        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json(['error' => 'La contraseña actual no es correcta.'], 400);
        }

        try {
            $user->password = Hash::make($validated['new_password']);
            $user->save();

            return response()->json(['message' => 'Contraseña actualizada correctamente.'], 200);
        } catch (\Exception $e) {
            \Log::error('Error al actualizar la contraseña: ' . $e->getMessage());
            return response()->json([
                'error' => 'Ocurrió un problema al actualizar la contraseña.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    // Update user info.
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'username' => 'sometimes|string|max:100|unique:users,username,' . $id,
            'email' => 'sometimes|email|max:100|unique:users,email,' . $id,
            'first_name' => 'nullable|string|max:80',
            'last_name' => 'nullable|string|max:80',
            'image' => 'nullable|string',
            'status' => 'boolean',
            'phone_number' => 'nullable|string|max:20',
            'role' => 'in:ADMIN,SELLER,CUSTOMER',
            'password' => 'nullable|string|min:6',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Usuario actualizado correctamente',
            'user' => $user,
        ], 200);
    }

    // Delete user and related data.
    public function destroy($id)
    {
        try {
            $user = User::with([
                'store.products.productReviews',
                'store.banners',
                'store.storeSocials',
                'orders',
                'addresses',
                'productReviews',
                'storeReviews',
                'transactions',
            ])->findOrFail($id);

            if ($user->store) {
                foreach ($user->store->products as $product) {
                    $product->productReviews()->delete();
                }
                $user->store->banners()->delete();
                $user->store->storeSocials()->delete();
                $user->store->products()->delete();
                $user->store->delete();
            }

            $user->orders()->delete();
            $user->addresses()->delete();
            $user->productReviews()->delete();
            $user->storeReviews()->delete();
            $user->transactions()->delete();
            $user->delete();

            return response()->json(['message' => 'Usuario y sus relaciones eliminados correctamente'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al eliminar el usuario',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    // Update user's status.
    public function updateStatus($id, Request $request)
    {
        $user = User::findOrFail($id);
        $user->status = $request->status;
        $user->save();

        return response()->json(['message' => 'Estado actualizado correctamente']);
    }
}
