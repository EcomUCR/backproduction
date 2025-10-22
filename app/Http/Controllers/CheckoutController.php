<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    /**
     * 🧾 Inicializa una nueva orden antes del pago.
     * Crea la orden base con estado "PENDING" y datos del cliente.
     */
    public function init(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'error' => true,
                'message' => 'Usuario no autenticado',
            ], 401);
        }

        // ✅ Validar datos del pedido base
        $validated = $request->validate([
            'subtotal' => 'required|numeric|min:0',
            'shipping' => 'nullable|numeric|min:0',
            'taxes' => 'nullable|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'street' => 'nullable|string|max:150',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
        ]);

        try {
            // 🧾 Crear la orden inicial (sin items todavía)
            $order = Order::create([
                'user_id' => $user->id,
                'status' => 'PENDING',
                'subtotal' => $validated['subtotal'],
                'shipping' => $validated['shipping'] ?? 0,
                'taxes' => $validated['taxes'] ?? 0,
                'total' => $validated['total'],
                'street' => $validated['street'] ?? null,
                'city' => $validated['city'] ?? null,
                'state' => $validated['state'] ?? null,
                'zip_code' => $validated['zip_code'] ?? null,
                'country' => $validated['country'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Orden inicial creada correctamente ✅',
                'order' => $order,
            ], 201);
        } catch (\Throwable $e) {
            Log::error('❌ Error al crear orden inicial', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => true,
                'message' => 'Error al crear la orden inicial',
                'detail' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
            ], 500);
        }
    }

    /**
     * 💳 Confirma el pago y actualiza la orden.
     * Cambia el estado, guarda el ID del pago y el método de pago.
     */
    public function confirm(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'error' => true,
                'message' => 'Usuario no autenticado',
            ], 401);
        }

        // ✅ Validar los datos de confirmación del pago
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'status' => 'required|string|in:PAID,FAILED,CANCELLED',
            'payment_id' => 'nullable|string|max:100',
            'payment_method' => 'nullable|string|max:30',
            'currency' => 'nullable|string|max:10',
        ]);

        try {
            $order = Order::findOrFail($validated['order_id']);

            $order->update([
                'status' => strtoupper($validated['status']),
                'payment_id' => $validated['payment_id'] ?? 'N/A',
                'payment_method' => strtoupper($validated['payment_method'] ?? 'CARD'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Orden confirmada correctamente 💳',
                'order' => $order->load('items.product'),
            ], 200);
        } catch (\Throwable $e) {
            Log::error('❌ Error al confirmar orden', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'error' => true,
                'message' => 'Error al confirmar la orden',
                'detail' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
            ], 500);
        }
    }
}
