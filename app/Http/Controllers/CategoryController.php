<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    // Retrieve and return all categories.
    public function index()
    {
        $categories = Category::all();
        return response()->json($categories);
    }

    // Retrieve and return a specific category by its ID.
    public function show($id)
    {
        $category = Category::findOrFail($id);
        return response()->json($category);
    }

    // Create a new category with the provided name.
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:50|unique:categories',
        ]);

        $category = Category::create($validatedData);

        return response()->json($category, 201);
    }

    // Update an existing category with the provided name.
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:50|unique:categories,name,'.$category->id,
        ]);

        $category->update($validatedData);

        return response()->json($category);
    }

    // Delete a category.
    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return response()->json(null, 204);
    }
}