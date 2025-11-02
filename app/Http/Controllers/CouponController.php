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
   // arriba del controlador
use Illuminate\Validation\Rule;

public function store(Request $request)
{
    $user = $request->user();

    // 1) Validación base (sin la unicidad todavía)
    $validated = $request->validate([
        'code'           => ['required','string','max:50'],
        'description'    => 'nullable|string',
        'type'           => ['required', Rule::in(['PERCENTAGE','FIXED','FREE_SHIPPING'])],
        'value'          => ['required','numeric','min:0'],
        'min_purchase'   => 'nullable|numeric|min:0',
        'max_discount'   => 'nullable|numeric|min:0',
        'store_id'       => 'nullable|exists:stores,id',   // ADMIN la enviará; SELLER se ignora
        'category_id'    => 'nullable|exists:categories,id',
        'product_id'     => 'nullable|exists:products,id',
        'user_id'        => 'nullable|exists:users,id',
        'usage_limit'    => ['required','integer','min:1'],
        'usage_per_user' => ['required','integer','min:1'],
        'expires_at'     => 'nullable|date',
        'active'         => 'boolean',
    ]);

    // 2) Resolver store_id según rol
    if ($user->role === 'SELLER') {
        $store = $user->store;
        if (!$store) {
            return response()->json(['message' => 'El vendedor no tiene una tienda asociada.'], 403);
        }
        $storeId = $store->id;
    } elseif ($user->role === 'ADMIN') {
        if (empty($validated['store_id'])) {
            return response()->json(['message' => 'store_id es requerido para crear cupones como ADMIN.'], 422);
        }
        $storeId = (int) $validated['store_id'];
    } else {
        return response()->json(['message' => 'Rol no autorizado.'], 403);
    }

    // 3) Unicidad por tienda (no global)
    $exists = \App\Models\Coupon::where('code', $validated['code'])
        ->where('store_id', $storeId)
        ->exists();

    if ($exists) {
        return response()->json(['message' => 'Ya existe un cupón con ese código en esta tienda.'], 422);
    }

    // 4) Ajuste FREE_SHIPPING (valor 0)
    if ($validated['type'] === 'FREE_SHIPPING') {
        $validated['value'] = 0;
    }

    // 5) Asignar store_id efectivo y crear
    $validated['store_id'] = $storeId;

    $coupon = \App\Models\Coupon::create($validated);
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
    $user   = $request->user();
    $coupon = \App\Models\Coupon::findOrFail($id);

    // Autorización: ADMIN o dueño del cupón (SELLER con misma tienda)
    if (
        $user->role !== 'ADMIN' &&
        (!$user->store || $coupon->store_id !== $user->store->id)
    ) {
        return response()->json(['message' => 'No autorizado para actualizar este cupón.'], 403);
    }

    $validated = $request->validate([
        'code'           => ['sometimes','string','max:50'],
        'description'    => 'nullable|string',
        'type'           => ['sometimes', Rule::in(['PERCENTAGE','FIXED','FREE_SHIPPING'])],
        'value'          => ['sometimes','numeric','min:0'],
        'min_purchase'   => 'nullable|numeric|min:0',
        'max_discount'   => 'nullable|numeric|min:0',
        // store_id NO se permite cambiar para seller; para admin podrías permitirlo si querés,
        // pero aquí lo mantenemos fijo al del cupón:
        // 'store_id'    => 'prohibido' (implícito)
        'category_id'    => 'nullable|exists:categories,id',
        'product_id'     => 'nullable|exists:products,id',
        'user_id'        => 'nullable|exists:users,id',
        'usage_limit'    => ['sometimes','integer','min:1'],
        'usage_per_user' => ['sometimes','integer','min:1'],
        'expires_at'     => 'nullable|date',
        'active'         => 'boolean',
    ]);

    // Unicidad por tienda al actualizar (ignorando el propio cupón)
    if (array_key_exists('code', $validated)) {
        $exists = \App\Models\Coupon::where('code', $validated['code'])
            ->where('store_id', $coupon->store_id)
            ->where('id', '!=', $coupon->id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Ya existe otro cupón con ese código en esta tienda.'], 422);
        }
    }

    if (($validated['type'] ?? $coupon->type) === 'FREE_SHIPPING') {
        $validated['value'] = 0;
    }

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
