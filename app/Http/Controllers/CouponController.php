<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CouponController extends Controller
{
    /**
     * 🧾 Listar cupones
     * - ADMIN: ve todos
     * - SELLER: solo ve los de su tienda
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Admin ve todos
        if ($user->role === 'ADMIN') {
            $coupons = Coupon::with(['store', 'category', 'product', 'user'])
                ->orderByDesc('created_at')
                ->get();
        } 
        // Seller ve solo los de su tienda
        else if ($user->role === 'SELLER') {
            $store = $user->store;
            if (!$store) {
                return response()->json(['message' => 'El vendedor no tiene una tienda asociada.'], 403);
            }

            $coupons = Coupon::with(['store', 'category', 'product', 'user'])
                ->where('store_id', $store->id)
                ->orderByDesc('created_at')
                ->get();
        } 
        else {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        return response()->json($coupons);
    }

    /**
     * ➕ Crear cupón
     * - ADMIN: puede asignar cualquier tienda
     * - SELLER: se asigna automáticamente a su tienda
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:coupons,code',
            'description' => 'nullable|string',
            'type' => 'required|in:PERCENTAGE,FIXED,FREE_SHIPPING',
            'value' => 'required|numeric|min:0',
            'min_purchase' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'store_id' => 'nullable|exists:stores,id',
            'category_id' => 'nullable|exists:categories,id',
            'product_id' => 'nullable|exists:products,id',
            'user_id' => 'nullable|exists:users,id',
            'usage_limit' => 'required|integer|min:1',
            'usage_per_user' => 'required|integer|min:1',
            'expires_at' => 'nullable|date',
            'active' => 'boolean',
        ]);

        // Verificar rol y asignar tienda si es vendedor
        if ($user->role === 'SELLER') {
            $store = $user->store;
            if (!$store) {
                return response()->json(['message' => 'El vendedor no tiene una tienda asociada.'], 403);
            }
            $validated['store_id'] = $store->id;
        }

        // Validar unicidad del código
        $existing = Coupon::where('code', $validated['code'])->first();
        if ($existing) {
            return response()->json([
                'message' => 'El código de cupón ya existe.',
                'coupon' => $existing,
            ], 409);
        }

        $coupon = Coupon::create($validated);
        return response()->json($coupon, 201);
    }

    /**
     * 👁️ Mostrar un cupón específico
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $coupon = Coupon::with(['store', 'category', 'product', 'user'])->findOrFail($id);

        // Solo admins o el dueño del cupón (vendedor)
        if (
            $user->role !== 'ADMIN' &&
            (!$user->store || $coupon->store_id !== $user->store->id)
        ) {
            return response()->json(['message' => 'No autorizado para ver este cupón.'], 403);
        }

        return response()->json($coupon);
    }

    /**
     * ✏️ Actualizar cupón
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $coupon = Coupon::findOrFail($id);

        // Solo admin o dueño del cupón
        if (
            $user->role !== 'ADMIN' &&
            (!$user->store || $coupon->store_id !== $user->store->id)
        ) {
            return response()->json(['message' => 'No autorizado para actualizar este cupón.'], 403);
        }

        $validated = $request->validate([
            'code' => 'sometimes|string|max:50|unique:coupons,code,' . $coupon->id,
            'description' => 'nullable|string',
            'type' => 'sometimes|in:PERCENTAGE,FIXED,FREE_SHIPPING',
            'value' => 'sometimes|numeric|min:0',
            'min_purchase' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'product_id' => 'nullable|exists:products,id',
            'user_id' => 'nullable|exists:users,id',
            'usage_limit' => 'sometimes|integer|min:1',
            'usage_per_user' => 'sometimes|integer|min:1',
            'expires_at' => 'nullable|date',
            'active' => 'boolean',
        ]);

        $coupon->update($validated);
        return response()->json($coupon);
    }

    /**
     * 🗑️ Eliminar cupón
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $coupon = Coupon::findOrFail($id);

        // Solo admin o dueño
        if (
            $user->role !== 'ADMIN' &&
            (!$user->store || $coupon->store_id !== $user->store->id)
        ) {
            return response()->json(['message' => 'No autorizado para eliminar este cupón.'], 403);
        }

        $coupon->delete();
        return response()->json(['message' => 'Cupón eliminado correctamente']);
    }

    /**
     * 🧠 Validar cupón antes de aplicarlo
     */
    public function validateCoupon(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string',
            'user_id' => 'nullable|integer',
            'total' => 'required|numeric|min:0',
            'store_id' => 'nullable|integer', // 👈 para validar tienda si aplica
        ]);

        $coupon = Coupon::where('code', $validated['code'])
            ->where('active', true)
            ->first();

        if (!$coupon) {
            return response()->json(['message' => 'Cupón no encontrado o inactivo'], 404);
        }

        // 🚫 Verificar si el cupón pertenece a otra tienda
        if (
            $coupon->store_id &&
            isset($validated['store_id']) &&
            $validated['store_id'] != $coupon->store_id
        ) {
            return response()->json(['message' => 'Este cupón no aplica a esta tienda'], 403);
        }

        if ($coupon->isExpired()) {
            return response()->json(['message' => 'El cupón ha expirado'], 400);
        }

        if ($coupon->min_purchase && $validated['total'] < (float) $coupon->min_purchase) {
            return response()->json([
                'message' => "El total debe ser al menos ₡" . number_format((float) $coupon->min_purchase, 2)
            ], 400);
        }

        $discount = 0;

        if ($coupon->type === 'PERCENTAGE') {
            $discount = ($validated['total'] * $coupon->value) / 100;
            if ($coupon->max_discount && $discount > $coupon->max_discount) {
                $discount = $coupon->max_discount;
            }
        } elseif ($coupon->type === 'FIXED') {
            $discount = $coupon->value;
        }

        return response()->json([
            'valid' => true,
            'discount' => round($discount, 2),
            'coupon' => $coupon,
        ]);
    }
}
