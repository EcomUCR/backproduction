<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        return response()->json($users);
    }
    public function me(Request $request)
{
    // Verificar si hay usuario autenticado
    $user = $request->user();

    if (!$user) {
        return response()->json([
            'message' => 'No se encontró un usuario autenticado.'
        ], 401);
    }

    // Cargar la tienda y opcionalmente productos o relaciones adicionales
    $user->load([
        'store:id,user_id,name,slug,status', // campos específicos de la tienda
    ]);

    return response()->json($user);
}

    public function show($id)
    {
        $user = User::with('store:id,user_id,name,slug,status')->findOrFail($id);
        return response()->json($user);
    }

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

    $validatedData['password'] = bcrypt($validatedData['password']);

    DB::beginTransaction();

    try {
        $user = User::create($validatedData);

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

        DB::commit();

        return response()->json([
            'message' => 'Usuario creado correctamente',
            'user' => $user
        ], 201);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'error' => 'Error al crear el usuario o la tienda',
            'details' => $e->getMessage()
        ], 500);
    }
}



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
        $validatedData['password'] = bcrypt($validatedData['password']);
    }

    $user->update($validatedData);

    // Cargar relación si existe
    $user->load('store:id,user_id,name,slug,status');

    return response()->json([
        'message' => 'Usuario actualizado correctamente',
        'user' => $user
    ]);
}

    public function login(Request $request)
    {
        // Validar credenciales

        $credentials = $request->validate([
            'email' => 'required|string|email|max:100',
            'password' => 'required|string|min:6',
        ]);
        \Log::info('POST /login recibido', [
            'request_all' => $request->all(),
            'credentials' => $credentials,
        ]);
        // Verificar si el usuario existe
        $user = User::where('email', $credentials['email'])->first();
        \Log::info('Usuario encontrado', [
            'user' => $user ? $user->toArray() : null,
        ]);
        if (!$user || !\Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'error' => 'Credenciales inválidas'
            ], 401);
        }
        if ($user) {
            $check = \Hash::check($credentials['password'], $user->password);
            \Log::info('Result de Hash::check', [
                'check' => $check,
                'written' => $credentials['password'],
                'hash_in_db' => $user->password,
            ]);
        }
        // Generar un nuevo token de acceso con Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }

   public function getStore($id)
{
    // Buscar el usuario junto con su tienda y relaciones relevantes
    $user = User::with([
        'store' => function ($query) {
            $query->select('id', 'user_id', 'name', 'slug', 'description', 'category_id', 'status')
                  ->with(['products:id,store_id,name,price,image_url', 'storeSocials', 'banners']);
        }
    ])->findOrFail($id);

    // Validar que el usuario tenga una tienda
    if (!$user->store) {
        return response()->json([
            'message' => 'El usuario no tiene una tienda asociada'
        ], 404);
    }

    // Respuesta limpia y estructurada
    return response()->json([
        'message' => 'Tienda encontrada correctamente',
        'store' => $user->store
    ]);
}


   public function destroy($id)
{
    try {
        $user = User::with(['store.products', 'store.banners', 'store.storeSocials', 'orders', 'addresses', 'productReviews', 'storeReviews', 'transactions'])->findOrFail($id);

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
            'details' => $e->getMessage()
        ], 500);
    }
}


}