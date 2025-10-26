<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CouponController extends Controller
{
    // List all coupons along with their related store, category, product, and user.
    public function index()
    {
        $coupons = Coupon::with(['store', 'category', 'product', 'user'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json($coupons);
    }

    // Create a new coupon with validation for uniqueness, type, value limits, and usage rules.
    public function store(Request $request)
    {
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

    // Retrieve a specific coupon along with its related store, category, product, and user.
    public function show($id)
    {
        $coupon = Coupon::with(['store', 'category', 'product', 'user'])->findOrFail($id);
        return response()->json($coupon);
    }

    // Update an existing coupon with optional data while validating business rules.
    public function update(Request $request, $id)
    {
        $coupon = Coupon::findOrFail($id);

        $validated = $request->validate([
            'code' => 'sometimes|string|max:50|unique:coupons,code,' . $coupon->id,
            'description' => 'nullable|string',
            'type' => 'sometimes|in:PERCENTAGE,FIXED,FREE_SHIPPING',
            'value' => 'sometimes|numeric|min:0',
            'min_purchase' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'store_id' => 'nullable|exists:stores,id',
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

    // Delete a specific coupon.
    public function destroy($id)
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->delete();

        return response()->json(['message' => 'Cupón eliminado correctamente']);
    }

    // Validate a coupon before applying it to a payment.
    public function validateCoupon(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string',
            'user_id' => 'nullable|integer',
            'total' => 'required|numeric|min:0',
        ]);

        $coupon = Coupon::where('code', $validated['code'])
            ->where('active', true)
            ->first();

        if (!$coupon) {
            return response()->json(['message' => 'Cupón no encontrado o inactivo'], 404);
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
        } elseif ($coupon->type === 'FREE_SHIPPING') {
            $discount = 0;
        }

        return response()->json([
            'valid' => true,
            'discount' => round($discount, 2),
            'coupon' => $coupon,
        ]);
    }
}
