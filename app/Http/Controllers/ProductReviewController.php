<?php

namespace App\Http\Controllers;

use App\Models\ProductReview;
use Illuminate\Http\Request;

class ProductReviewController extends Controller
{
    // Retrieve all product reviews.
    public function index()
    {
        $productReviews = ProductReview::all();
        return response()->json($productReviews);
    }

    // Retrieve a specific product review by ID.
    public function show($id)
    {
        $productReview = ProductReview::findOrFail($id);
        return response()->json($productReview);
    }

    // Create a new product review.
   public function store(Request $request, $product_id)
{
    $user = $request->user();

    $validatedData = $request->validate([
        'rating' => 'required|integer|min:1|max:5',
        'comment' => 'nullable|string',
    ]);

    // Asignar datos automáticos
    $validatedData['user_id'] = $user->id;
    $validatedData['product_id'] = $product_id; // 🔹 se obtiene de la ruta

    $productReview = ProductReview::create($validatedData);

    // Actualizar promedio
    $productReview->product->updateRatingFromReviews();

    return response()->json([
        'message' => 'Reseña creada correctamente',
        'review' => $productReview
    ], 201);
}




    public function indexByProduct($product_id)
{
    $reviews = ProductReview::with('user:id,first_name,last_name,username,image')
        ->where('product_id', $product_id)
        ->latest()
        ->get();

    return response()->json($reviews);
}

public function summary($product_id)
{
    $reviews = ProductReview::where('product_id', $product_id)->get();

    if ($reviews->count() === 0) {
        return response()->json([
            'average' => 0,
            'total' => 0,
            'distribution' => [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0],
        ]);
    }

    $distribution = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
    foreach ($reviews as $review) {
        $distribution[$review->rating]++;
    }

    $average = round($reviews->avg('rating'), 2);

    return response()->json([
        'average' => $average,
        'total' => $reviews->count(),
        'distribution' => $distribution,
    ]);
}


    // Update an existing product review.
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

    // Delete a product review.
    public function destroy($id)
    {
        $productReview = ProductReview::findOrFail($id);
        $productReview->delete();

        return response()->json(null, 204);
    }
}