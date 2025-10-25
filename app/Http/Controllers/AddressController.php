<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    /**
     * 📦 Listar direcciones del usuario autenticado
     */
    public function index(Request $request)
    {
        return response()->json($request->user()->addresses);
    }

    /**
     * ➕ Crear nueva dirección
     */
    public function store(Request $request)
    {
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
            $request->user()->addresses()->update(['is_default' => false]);
        }

        $address = $request->user()->addresses()->create($validated);

        return response()->json($address, 201);
    }

    /**
     * ✏️ Actualizar dirección existente
     */
    public function update(Request $request, $id)
    {
        $address = Address::where('customer_id', $request->user()->id)->findOrFail($id);

        $validated = $request->validate([
            'street' => 'sometimes|string|max:255',
            'city' => 'sometimes|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'phone_number' => 'nullable|string|max:20',
            'is_default' => 'boolean',
        ]);

        // Si marca esta dirección como predeterminada
        if (!empty($validated['is_default']) && $validated['is_default']) {
            $request->user()->addresses()->update(['is_default' => false]);
        }

        $address->update($validated);

        return response()->json($address);
    }

    /**
     * 🗑️ Eliminar dirección
     */
    public function destroy(Request $request, $id)
    {
        $address = Address::where('customer_id', $request->user()->id)->findOrFail($id);
        $address->delete();

        return response()->json(['message' => 'Dirección eliminada correctamente.']);
    }
}
