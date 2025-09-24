<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    /**
     * Listar todos los productos
     */
    public function index()
    {
        return Product::with(['categories', 'images', 'vendor'])->get();
    }

    /**
     * Mostrar un solo producto
     */
    public function show($id)
    {
        return Product::with(['categories', 'images', 'vendor'])->findOrFail($id);
    }

    /**
     * Crear un producto
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'required|numeric',
            'discount'    => 'nullable|numeric',
            'stock'       => 'required|integer',
            'status'      => 'required|boolean',
            'categories'  => 'array',
            'categories.*'=> 'exists:categories,id',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $vendorId = Auth::user()->vendor->id;

        $product = Product::create([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price'       => $validated['price'],
            'discount'    => $validated['discount'] ?? 0,
            'stock'       => $validated['stock'],
            'status'      => $validated['status'],
            'vendor_id'   => $vendorId,
        ]);

        // Categorías
        if (!empty($validated['categories'])) {
            $product->categories()->sync($validated['categories']);
        }

        // Imagen
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $product->images()->create([
                'url'   => $path,
                'order' => 1,
            ]);
        }

        return response()->json($product->load(['categories', 'images']), 201);
    }

    /**
     * Actualizar un producto
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'required|numeric',
            'discount'    => 'nullable|numeric',
            'stock'       => 'required|integer',
            'status'      => 'required|boolean',
            'categories'  => 'array',
            'categories.*'=> 'exists:categories,id',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $product = Product::findOrFail($id);

        $product->update([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price'       => $validated['price'],
            'discount'    => $validated['discount'] ?? 0,
            'stock'       => $validated['stock'],
            'status'      => $validated['status'],
        ]);

        if (!empty($validated['categories'])) {
            $product->categories()->sync($validated['categories']);
        }

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $product->images()->create([
                'url'   => $path,
                'order' => 1,
            ]);
        }

        return response()->json($product->load(['categories', 'images']), 200);
    }

    /**
     * Eliminar un producto
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json(['message' => 'Producto eliminado correctamente']);
    }

    /**
     * Buscar productos por nombre
     */
    public function search(Request $request)
    {
        $query = Product::query();

        if ($search = $request->input('q')) {
            $query->where('name', 'like', "%$search%");
        }

        return $query->with(['categories', 'images'])->get();
    }

    /**
     * Productos por vendor
     */
    public function byVendor($vendorId)
    {
        return Product::with(['categories', 'images'])
            ->where('vendor_id', $vendorId)
            ->get();
    }
}
