<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        return response()->json($users);
    }

    public function show($id)
    {
        $user = User::findOrFail($id);
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
        $user = User::create($validatedData);

        return response()->json($user, 201);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validatedData = $request->validate([
            'username' => 'sometimes|string|max:100|unique:users,',
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

        return response()->json($user);
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

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(null, 204);
    }
}