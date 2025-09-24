<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ClientController extends Controller
{
    // 🔹 Update a Client + User
    public function update(Request $request, $id)
    {
        $client = Client::with('user')->findOrFail($id);

        $request->validate([
            'email'      => 'required|email|unique:users,email,' . $client->user->id,
            'password'   => 'nullable|min:6',
            'username'   => 'required|string|max:50',
            'first_name' => 'required|string|max:50',
            'last_name'  => 'required|string|max:50',
            'address'    => 'nullable|string|max:255',
        ]);

        // 🔹 Update User
        $user = $client->user;
        $user->email = $request->email;
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        $user->save();

        // 🔹 Update Client
        $client->update([
            'username'   => $request->username,
            'first_name' => $request->first_name,
            'last_name'  => $request->last_name,
            'address'    => $request->address,
        ]);

        return response()->json([
            'message' => 'Client updated successfully',
            'client'  => $client->load('user')
        ]);
    }
}
