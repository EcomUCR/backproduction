<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AddressController extends Controller
{
    /**
     * 📦 Listar direcciones del usuario autenticado
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['error' => 'Usuario no autenticado'], 401);
            }

            return response()->json($user->addresses);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Error al obtener direcciones',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    /**
     * ➕ Crear nueva dirección
     */
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

            // Si la nueva dirección es predeterminada, desactivar las demás
            if (!empty($validated['is_default']) && $validated['is_default']) {
                $user->addresses()->update(['is_default' => false]);
            }

            // Crear la dirección
            $address = $user->addresses()->create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Dirección creada correctamente',
                'address' => $address
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Error de validación',
                'details' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Error interno del servidor',
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ], 500);
        }
    }

    /**
     * ✏️ Actualizar dirección existente
     */
    public function update(Request $request, $id)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['error' => 'Usuario no autenticado'], 401);
            }

            $address = Address::where('customer_id', $user->id)->findOrFail($id);

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
                'address' => $address
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Error al actualizar la dirección',
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ], 500);
        }
    }

    /**
     * 🗑️ Eliminar dirección
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['error' => 'Usuario no autenticado'], 401);
            }

            $address = Address::where('customer_id', $user->id)->findOrFail($id);
            $address->delete();

            return response()->json(['success' => true, 'message' => 'Dirección eliminada correctamente']);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Error al eliminar dirección',
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ], 500);
        }
    }
}
