<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\Cart;
class UserController extends Controller
{
    /**
     * Listar todos los usuarios
     */
    public function index()
    {
        $users = User::with([
            'store' => function ($query) {
                $query->select('id', 'user_id', 'name', 'category_id', 'image', 'banner', 'slug')
                    ->with(['storeSocials', 'banners']);
            },
        ])->get(['id', 'username', 'email', 'first_name', 'last_name', 'image', 'status', 'phone_number', 'role', 'created_at', 'updated_at']); // columnas del usuario

        return response()->json($users);
    }


    /**
     * Obtener el usuario autenticado con sus datos relacionados
     */
    public function me(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'No se encontró un usuario autenticado.'
            ], 401);
        }

        // Cargar la tienda y las direcciones del usuario
        $user->load([
            'store:id,user_id,name,slug,description,image,banner,registered_address,support_phone,support_email,status,is_verified'
        ]);


        return response()->json($user);
    }
    public function userAddresses(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'error' => 'Usuario no autenticado'
            ], 401);
        }

        // ✅ Obtener direcciones del usuario autenticado
        $addresses = $user->addresses()
            ->select('id', 'street', 'city', 'state', 'zip_code', 'country', 'phone_number', 'is_default')
            ->orderByDesc('is_default')
            ->get();

        return response()->json([
            'success' => true,
            'addresses' => $addresses
        ]);
    }

    /**
     * Mostrar un usuario por ID
     */
    public function show($id)
    {
        $user = User::with('store:id,user_id,name,slug,status')->findOrFail($id);
        return response()->json($user);
    }

    /**
     * Registrar un nuevo usuario
     */
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

        // 🔐 Encriptar contraseña
        $validatedData['password'] = Hash::make($validatedData['password']);

        try {
            // 👤 Crear usuario
            $user = User::create($validatedData);

            // 🛒 Crear carrito
            $cart = Cart::create(['user_id' => $user->id]);
            $user->setRelation('cart', $cart);

            // 🏬 Si es vendedor, crear tienda y notificar a los administradores
            if ($user->role === 'SELLER') {
                $store = $user->store()->create([
                    'name' => $user->username,
                    'slug' => Str::slug($user->username) . '-' . $user->id,
                    'category_id' => 1,
                    'status' => 'ACTIVE',
                ]);

                $user->setRelation('store', $store);

                // 🔔 Crear notificación interna
                $admins = \App\Models\User::where('role', 'ADMIN')->get();

                foreach ($admins as $admin) {
                    \App\Models\Notification::create([
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

                // 📧 Enviar correo HTML a todos los administradores
                $admins = \App\Models\User::where('role', 'ADMIN')->get(['email']);

                if ($admins->isNotEmpty()) {
                    $subject = 'Nueva solicitud de verificación de tienda';
                    $body = view('emails.verification-request-html', data: [
                        'store_name' => $store->name,
                        'owner_name' => trim($user->first_name . ' ' . $user->last_name) ?: $user->username,
                        'owner_email' => $user->email,
                        'owner_phone' => $user->phone_number ?? 'No especificado',
                        'request_date' => now()->format('d/m/Y H:i'),
                        'admin_url' => env('ADMIN_PANEL_URL', 'https://tukishopcr.com/admin/stores'),
                    ])->render();

                    foreach ($admins as $admin) {
                        \App\Services\BrevoMailer::send($admin->email, $subject, $body);
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

   public function changePassword(Request $request)
{
    $user = $request->user();

    if (!$user) {
        return response()->json(['error' => 'Usuario no autenticado.'], 401);
    }

    $validated = $request->validate([
        'current_password' => 'required|string',
        'new_password' => 'required|string|min:6|confirmed', // requiere new_password_confirmation
    ]);

    // Verificar contraseña actual
    if (!\Hash::check($validated['current_password'], $user->password)) {
        return response()->json([
            'error' => 'La contraseña actual no es correcta.'
        ], 400);
    }

    try {
        // Actualizar contraseña
        $user->password = \Hash::make($validated['new_password']);
        $user->save();

        return response()->json([
            'message' => 'Contraseña actualizada correctamente.'
        ], 200);
    } catch (\Exception $e) {
        \Log::error('Error al actualizar la contraseña: ' . $e->getMessage());
        return response()->json([
            'error' => 'Ocurrió un problema al actualizar la contraseña.',
            'details' => $e->getMessage()
        ], 500);
    }
}



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

        // 🔐 Si viene contraseña nueva, encriptarla
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Usuario actualizado correctamente',
            'user' => $user
        ], 200);
    }


    /**
     * Iniciar sesión
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|string|email|max:100',
            'password' => 'required|string|min:6',
        ]);

        // Buscar usuario
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'error' => 'Credenciales inválidas'
            ], 401);
        }

        // Crear token de acceso con Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }

    /**
     * Obtener la tienda asociada a un usuario
     */
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
            return response()->json([
                'message' => 'El usuario no tiene una tienda asociada'
            ], 404);
        }

        return response()->json([
            'message' => 'Tienda encontrada correctamente',
            'store' => $user->store
        ]);
    }


    /**
     * Eliminar usuario y todas sus relaciones
     */
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
                'transactions'
            ])->findOrFail($id);

            if ($user->store) {
                // Eliminar reseñas de productos antes de borrar los productos
                foreach ($user->store->products as $product) {
                    $product->productReviews()->delete();
                }

                $user->store->banners()->delete();
                $user->store->storeSocials()->delete();
                $user->store->products()->delete();
                $user->store->delete();
            }

            // Eliminar otras relaciones
            $user->orders()->delete();
            $user->addresses()->delete();
            $user->productReviews()->delete();
            $user->storeReviews()->delete();
            $user->transactions()->delete();

            $user->delete();

            return response()->json([
                'message' => 'Usuario y sus relaciones eliminados correctamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al eliminar el usuario',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function updateStatus($id, Request $request)
    {
        $user = User::findOrFail($id);
        $user->status = $request->status;
        $user->save();

        return response()->json(['message' => 'Estado actualizado correctamente']);
    }
}
