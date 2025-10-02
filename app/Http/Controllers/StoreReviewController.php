<?php

namespace App\Http\Controllers;

use App\Models\StoreReview;
use Illuminate\Http\Request;

class StoreReviewController extends Controller
{
    public function index()
    {
        $storeReviews = StoreReview::all();
        return response()->json($storeReviews);
    }

    public function show($id)
    {
        $storeReview = StoreReview::findOrFail($id);
        return response()->json($storeReview);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'user_id' => 'required|exists:users,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        $storeReview = StoreReview::create($validatedData);

        return response()->json($storeReview, 201);
    }

    public function update(Request $request, $id)
    {
        $storeReview = StoreReview::findOrFail($id);

        $validatedData = $request->validate([
            'rating' => 'sometimes|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        $storeReview->update($validatedData);

        return response()->json($storeReview);
    }

    public function destroy($id)
    {
        $storeReview = StoreReview::findOrFail($id);
        $storeReview->delete();

        return response()->json(null, 204);
    }
}