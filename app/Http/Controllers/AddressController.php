<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    // Retrieve and return all addresses for the authenticated user.
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Usuario no autenticado'], 401);
        }

        return response()->json([
            'success' => true,
            'addresses' => $user->addresses
        ]);
    }

    // Create a new address for the authenticated user and handle default address logic.
    public function store(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['error' => 'Usuario no autenticado'], 401);
            }

            $validated = $request->validate([
                'street' => 'required|string|max:255',
                'city' => 'required|string|max:100',
                'state' => 'nullable|string|max:100',
                'zip_code' => 'nullable|string|max:20',
                'country' => 'required|string|max:100',
                'phone_number' => 'nullable|string|max:20',
                'is_default' => 'boolean',
            ]);

            if (!empty($validated['is_default']) && $validated['is_default']) {
                $user->addresses()->update(['is_default' => false]);
            }

            $address = $user->addresses()->create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Dirección creada correctamente',
                'address' => $address,
            ], 201);

        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    // Update an existing address for the authenticated user and handle default address logic.
    public function update(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Usuario no autenticado'], 401);
        }

        $address = Address::where('user_id', $user->id)->findOrFail($id);

        $validated = $request->validate([
            'street' => 'sometimes|string|max:255',
            'city' => 'sometimes|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'phone_number' => 'nullable|string|max:20',
            'is_default' => 'boolean',
        ]);

        if (!empty($validated['is_default']) && $validated['is_default']) {
            $user->addresses()->update(['is_default' => false]);
        }

        $address->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Dirección actualizada correctamente',
            'address' => $address->fresh()
        ]);
    }

    // Delete a specific address of the authenticated user.
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Usuario no autenticado'], 401);
        }

        $address = Address::where('user_id', $user->id)->findOrFail($id);
        $address->delete();

        return response()->json([
            'success' => true,
            'message' => 'Dirección eliminada correctamente'
        ]);
    }
}
