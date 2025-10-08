<?php

namespace App\Http\Controllers;

use App\Models\ProductReview;
use Illuminate\Http\Request;

class ProductReviewController extends Controller
{
    public function index()
    {
        $productReviews = ProductReview::all();
        return response()->json($productReviews);
    }

    public function show($id)
    {
        $productReview = ProductReview::findOrFail($id);
        return response()->json($productReview);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'product_id' => 'required|exists:products,id',
            'user_id' => 'required|exists:users,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
            'likes' => 'nullable|integer',
            'dislikes' => 'nullable|integer',
        ]);

        $productReview = ProductReview::create($validatedData);

        return response()->json($productReview, 201);
    }

    public function update(Request $request, $id)
    {
        $productReview = ProductReview::findOrFail($id);

        $validatedData = $request->validate([
            'rating' => 'sometimes|integer|min:1|max:5',
            'comment' => 'nullable|string',
            'likes' => 'nullable|integer',
            'dislikes' => 'nullable|integer',
        ]);

        $productReview->update($validatedData);

        return response()->json($productReview);
    }

    public function destroy($id)
    {
        $productReview = ProductReview::findOrFail($id);
        $productReview->delete();

        return response()->json(null, 204);
    }
}