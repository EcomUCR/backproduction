<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Listar todos los usuarios
     */
    public function index()
    {
        $users = User::with('store:id,user_id,name,slug,status')->get();
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

        // Cargar la tienda del usuario
        $user->load([
            'store:id,user_id,name,slug,description,image,banner,registered_address,support_phone,status'
        ]);

        return response()->json($user);
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

        // Encriptar la contraseña
        $validatedData['password'] = Hash::make($validatedData['password']);

        try {
            // Crear usuario
            $user = User::create($validatedData);

            // Si es vendedor, crear tienda automáticamente
            if ($user->role === 'SELLER') {
                $defaultCategoryId = 1;
                $store = $user->store()->create([
                    'name' => $user->username,
                    'slug' => Str::slug($user->username) . '-' . $user->id,
                    'category_id' => $defaultCategoryId,
                    'status' => 'ACTIVE',
                ]);
                $user->setRelation('store', $store);
            }

            return response()->json([
                'message' => 'Usuario creado correctamente',
                'user' => $user
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al crear el usuario o la tienda',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar un usuario existente
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validatedData = $request->validate([
            'username' => 'sometimes|string|max:100|unique:users,username,' . $user->id,
            'email' => 'sometimes|string|email|max:100|unique:users,email,' . $user->id,
            'password' => 'sometimes|string|min:6',
            'first_name' => 'nullable|string|max:80',
            'last_name' => 'nullable|string|max:80',
            'phone_number' => 'nullable|string|max:20',
            'role' => 'sometimes|string|in:ADMIN,SELLER,CUSTOMER',
        ]);

        if (isset($validatedData['password'])) {
            $validatedData['password'] = Hash::make($validatedData['password']);
        }

        $user->update($validatedData);

        $user->load('store:id,user_id,name,slug,status');

        return response()->json([
            'message' => 'Usuario actualizado correctamente',
            'user' => $user
        ]);
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
                $query->select('id', 'user_id', 'name', 'slug', 'description', 'category_id', 'status')
                    ->with(['products:id,store_id,name,price,image_1_url']);
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
}
