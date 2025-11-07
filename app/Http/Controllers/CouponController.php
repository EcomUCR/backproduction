<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CouponController extends Controller
{
    /**
     * 🧾 Listar cupones
     * - ADMIN: ve todos
     * - SELLER: solo los de su tienda
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'ADMIN') {
            $coupons = Coupon::with(['store', 'category', 'product', 'user'])
                ->orderByDesc('created_at')
                ->get();
        } elseif ($user->role === 'SELLER') {
            $store = $user->store;
            if (!$store) {
                return response()->json(['message' => 'El vendedor no tiene una tienda asociada.'], 403);
            }

            $coupons = Coupon::with(['store', 'category', 'product', 'user'])
                ->where('store_id', $store->id)
                ->orderByDesc('created_at')
                ->get();
        } else {
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

        // ✅ 1) Validación base
        $validated = $request->validate([
            'code'           => ['required', 'string', 'max:50'],
            'description'    => 'nullable|string',
            'type'           => ['required', Rule::in(['PERCENTAGE', 'FIXED', 'FREE_SHIPPING'])],
            'value'          => ['required', 'numeric', 'min:0'],
            'min_purchase'   => 'nullable|numeric|min:0',
            'max_discount'   => 'nullable|numeric|min:0',
            'store_id'       => 'nullable|exists:stores,id',
            'category_id'    => 'nullable|exists:categories,id',
            'product_id'     => 'nullable|exists:products,id',
            'user_id'        => 'nullable|exists:users,id',
            'usage_limit'    => ['required', 'integer', 'min:1'],
            'usage_per_user' => ['required', 'integer', 'min:1'],
            'expires_at'     => 'nullable|date',
            'active'         => 'boolean',
        ]);

        // ✅ 2) Determinar tienda
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

        // ✅ 3) Unicidad por tienda
        $exists = Coupon::where('code', $validated['code'])
            ->where('store_id', $storeId)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Ya existe un cupón con ese código en esta tienda.'], 422);
        }

        // ✅ 4) Ajuste FREE_SHIPPING
        if (($validated['type'] ?? null) === 'FREE_SHIPPING') {
            $validated['value'] = 0;
            $validated['max_discount'] = null;
        }

        // ✅ 5) Crear cupón
        $validated['store_id'] = $storeId;
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

        if (
            $user->role !== 'ADMIN' &&
            (!$user->store || $coupon->store_id !== $user->store->id)
        ) {
            return response()->json(['message' => 'No autorizado para actualizar este cupón.'], 403);
        }

        $validated = $request->validate([
            'code'           => ['sometimes', 'string', 'max:50'],
            'description'    => 'nullable|string',
            'type'           => ['sometimes', Rule::in(['PERCENTAGE', 'FIXED', 'FREE_SHIPPING'])],
            'value'          => ['sometimes', 'numeric', 'min:0'],
            'min_purchase'   => 'nullable|numeric|min:0',
            'max_discount'   => 'nullable|numeric|min:0',
            'category_id'    => 'nullable|exists:categories,id',
            'product_id'     => 'nullable|exists:products,id',
            'user_id'        => 'nullable|exists:users,id',
            'usage_limit'    => ['sometimes', 'integer', 'min:1'],
            'usage_per_user' => ['sometimes', 'integer', 'min:1'],
            'expires_at'     => 'nullable|date',
            'active'         => 'boolean',
        ]);

        // Duplicado
        if (array_key_exists('code', $validated)) {
            $exists = Coupon::where('code', $validated['code'])
                ->where('store_id', $coupon->store_id)
                ->where('id', '!=', $coupon->id)
                ->exists();

            if ($exists) {
                return response()->json(['message' => 'Ya existe otro cupón con ese código en esta tienda.'], 422);
            }
        }

        // FREE_SHIPPING -> valor 0
        if (($validated['type'] ?? null) === 'FREE_SHIPPING') {
            $validated['value'] = 0;
            $validated['max_discount'] = null;
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
            'code'      => 'required|string',
            'user_id'   => 'nullable|integer',
            'total'     => 'required|numeric|min:0',
            'store_id'  => 'nullable|integer',
        ]);

        $user = $request->user();

        $coupon = Coupon::where('code', $validated['code'])
            ->where('active', true)
            ->first();

        if (!$coupon) {
            return response()->json(['message' => 'Cupón no encontrado o inactivo'], 404);
        }

        if ($coupon->isExpired()) {
            return response()->json(['message' => 'El cupón ha expirado'], 400);
        }

        // 🛒 Cargar carrito
        $cart = \App\Models\Cart::where('user_id', $user?->id)
            ->with('items.product')
            ->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['message' => 'El carrito está vacío'], 400);
        }

        // 🧩 Filtrar productos válidos
        $validItems = $cart->items->filter(function ($item) use ($coupon) {
            $product = $item->product;
            if (!$product) return false;

            if ($coupon->product_id && $coupon->product_id !== $product->id) return false;
            if ($coupon->category_id && $product->category_id !== $coupon->category_id) return false;
            if ($coupon->store_id && $product->store_id !== $coupon->store_id) return false;

            return true;
        });

        if ($validItems->isEmpty()) {
            return response()->json([
                'message' => 'El cupón no aplica a los productos de tu carrito.'
            ], 400);
        }

        // 💰 Subtotal de productos válidos
        $subtotal = 0;
        foreach ($validItems as $item) {
            $price = $item->product->discount_price ?? $item->product->price;
            $subtotal += $price * $item->quantity;
        }

        if ($coupon->min_purchase && $subtotal < $coupon->min_purchase) {
            return response()->json([
                'message' => "El total de productos aplicables debe ser al menos ₡" . number_format($coupon->min_purchase, 2)
            ], 400);
        }

        // ✅ Calcular descuento
        $discount = 0;

        if ($coupon->type === 'PERCENTAGE') {
            $discount = ($subtotal * $coupon->value) / 100;
            if ($coupon->max_discount && $discount > $coupon->max_discount) {
                $discount = $coupon->max_discount;
            }
        } elseif ($coupon->type === 'FIXED') {
            $discount = min($coupon->value, $subtotal);
        } elseif ($coupon->type === 'FREE_SHIPPING') {
            $discount = 0;
        }

        return response()->json([
            'valid'      => true,
            'discount'   => round($discount, 2),
            'coupon'     => $coupon,
            'applied_to' => $validItems->map(function ($item) {
                return [
                    'id'       => $item->product->id,
                    'name'     => $item->product->name,
                    'price'    => $item->product->discount_price ?? $item->product->price,
                    'quantity' => $item->quantity,
                ];
            })->values(),
            'context' => [
                'applies_to' => $coupon->product_id ? 'product' :
                    ($coupon->category_id ? 'category' :
                    ($coupon->store_id ? 'store' : 'global')),
                'scope_id' => $coupon->product_id ?? $coupon->category_id ?? $coupon->store_id,
            ],
        ]);
    }
}
