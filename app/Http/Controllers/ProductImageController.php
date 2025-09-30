<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductImageController extends Controller
{
    /**
     * Listar imágenes de un producto
     */
    public function index($productId)
    {
        $product = Product::with('images')->findOrFail($productId);

        return response()->json([
            'product' => $product->name,
            'images'  => $product->images
        ]);
    }

    /**
     * Guardar nueva imagen de un producto
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'image'      => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        // Guardar archivo en storage/app/public/products
        $path = $request->file('image')->store('products', 'public');

        // Guardar registro en la base de datos
        $image = ProductImage::create([
            'product_id' => $request->product_id,
            'url'        => "/storage/" . $path,
            'order'      => ProductImage::where('product_id', $request->product_id)->count() + 1,
        ]);

        return response()->json([
            'message' => 'Imagen subida correctamente',
            'data'    => $image
        ], 201);
    }

    /**
     * Eliminar una imagen
     */
    public function destroy($id)
    {
        $image = ProductImage::findOrFail($id);

        // Borrar archivo físico si existe
        if ($image->url && Storage::disk('public')->exists(str_replace('/storage/', '', $image->url))) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $image->url));
        }

        $image->delete();

        return response()->json(['message' => 'Imagen eliminada correctamente']);
    }
}
