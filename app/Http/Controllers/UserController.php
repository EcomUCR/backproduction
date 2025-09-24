<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Client;
use App\Models\Vendor;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class UserController extends Controller
{
    use AuthorizesRequests;

    // 🔹 Register User as Client or Vendor
    public function register(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'type' => 'required|in:client,vendor',
        ]);

        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        if ($request->type === 'client') {
            Client::create([
                'user_id' => $user->id,
                'username' => $request->username,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'address' => $request->address ?? null,
            ]);
        } else if ($request->type === 'vendor') {
            Vendor::create([
                'user_id' => $user->id,
                'name' => $request->name,
                'phone_number' => $request->phone_number ?? null,
            ]);
        }

        return response()->json(['message' => 'Registered successfully']);
    }

    // 🔹 Login
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->with('vendor')->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages(['email' => 'Invalid credentials']);
        }

        // 🔹 Update last login timestamp
        $user->last_login_at = now();
        $user->save();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
            'vendor_id' => $user->vendor ? $user->vendor->id : null,
        ]);
    }

    // 🔹 Change Password
    public function changePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|min:6|confirmed',
            // "confirmed" => espera también new_password_confirmation
        ]);

        $user = $request->user();

        // Verificar que la contraseña actual es correcta
        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json([
                'message' => 'La contraseña actual no es correcta'
            ], 422);
        }

        // Actualizar la contraseña
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'message' => 'Contraseña actualizada correctamente ✅'
        ]);
    }

    // 🔹 Get logged-in user info
    public function me(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'user' => $user,
            'client' => $user->client,
            'vendor' => $user->vendor,
            'staff' => $user->staff,
        ]);
    }

    // 🔹 Logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }
    // 🔹 Show User by ID
    public function show($id)
    {
        $user = User::with(['client', 'vendor', 'staff'])->findOrFail($id);
        return response()->json($user);
    }
    // 🔹 Delete a user by ID
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Optional: you can also delete related client/vendor/staff
        if ($user->client)
            $user->client->delete();
        if ($user->vendor)
            $user->vendor->delete();
        if ($user->staff)
            $user->staff->delete();

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    // 🔹 List Users
    public function listUsers()
    {
        $this->authorize('viewAny', User::class);
        $users = User::with(['client', 'vendor', 'staff'])->get();
        return response()->json($users);
    }
}
