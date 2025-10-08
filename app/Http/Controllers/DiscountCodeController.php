<?php

namespace App\Http\Controllers;

use App\Models\DiscountCode;
use Illuminate\Http\Request;

class DiscountCodeController extends Controller
{
    public function index()
    {
        $discountCodes = DiscountCode::all();
        return response()->json($discountCodes);
    }

    public function show($id)
    {
        $discountCode = DiscountCode::findOrFail($id);
        return response()->json($discountCode);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'code' => 'required|string|max:50|unique:discount_codes',
            'description' => 'nullable|string',
            'scope' => 'required|string|in:GLOBAL,PRODUCT',
            'admin_id' => 'nullable|exists:users,id',
            'store_id' => 'nullable|exists:stores,id',
            'product_id' => 'nullable|exists:products,id',
            'discount_pct' => 'required|integer',
            'max_uses' => 'required|integer',
            'used_count' => 'nullable|integer',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date',
            'is_active' => 'nullable|boolean',
        ]);

        $discountCode = DiscountCode::create($validatedData);

        return response()->json($discountCode, 201);
    }

    public function update(Request $request, $id)
    {
        $discountCode = DiscountCode::findOrFail($id);

        $validatedData = $request->validate([
            'code' => 'sometimes|string|max:50|unique:discount_codes,code,'.$discountCode->id,
            'description' => 'nullable|string',
            'scope' => 'sometimes|string|in:GLOBAL,PRODUCT',
            'admin_id' => 'nullable|exists:users,id',
            'store_id' => 'nullable|exists:stores,id',
            'product_id' => 'nullable|exists:products,id',
            'discount_pct' => 'sometimes|integer',
            'max_uses' => 'sometimes|integer' ,
            'used_count' => 'nullable|integer',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date',
            'is_active' => 'nullable|boolean',
        ]);

        $discountCode->update($validatedData);

        return response()->json($discountCode);
    }

    public function destroy($id)
    {
        $discountCode = DiscountCode::findOrFail($id);
        $discountCode->delete();

        return response()->json(null, 204);
    }
}