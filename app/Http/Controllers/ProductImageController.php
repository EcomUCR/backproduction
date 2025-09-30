<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;

class ProductImageController extends Controller
{
    /**
     * Listar imágenes de un producto
     */
    public function index($productId): JsonResponse
    {
        $product = Product::with('images')->findOrFail($productId);

        // Opcional: normalizar las URLs (si en BD están guardadas absolutas, ya vienen listas)
        $images = $product->images->map(function ($img) {
            // Si guardaste la URL absoluta, no toques nada
            if (preg_match('/^https?:\/\//', $img->url) || str_starts_with($img->url, config('app.url'))) {
                return $img;
            }
            // Si guardaste path relativo (p.ej. products/abc.jpg), exponla como absoluta
            $img->url = Storage::disk('public')->url(ltrim(str_replace('/storage/', '', $img->url), '/'));
            return $img;
        });

        return response()->json([
            'product' => $product->name,
            'images'  => $images,
        ]);
    }

    /**
     * Guardar nueva imagen de un producto
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'image'      => 'required|image|mimes:jpg,jpeg,png,webp,avif|max:4096',
        ]);

        // Guardar archivo en storage/app/public/products
        $path = $request->file('image')->store('products', 'public');

        // URL pública absoluta (APP_URL/storage/products/...)
        $publicUrl = Storage::disk('public')->url($path);

        // Calcular order = max + 1 (resistente a huecos por borrados)
        $nextOrder = (int) ProductImage::where('product_id', $request->product_id)->max('order') + 1;

        // Guardar registro en la base de datos
        $image = ProductImage::create([
            'product_id' => $request->product_id,
            'url'        => $publicUrl, // guardamos URL absoluta, React la puede consumir directo
            'order'      => $nextOrder,
        ]);

        return response()->json([
            'message' => 'Imagen subida correctamente',
            'data'    => $image
        ], 201);
    }

    /**
     * Eliminar una imagen
     */
    public function destroy($id): JsonResponse
    {
        $image = ProductImage::findOrFail($id);

        // Convertir la URL absoluta a path relativo del disco 'public' si aplica
        $disk = Storage::disk('public');
        $publicBase = rtrim($disk->url(''), '/'); // e.g. http://localhost:8000/storage

        $relativePath = $image->url;

        // Si la URL empieza por el base público, quedarnos con el resto como path relativo
        if (str_starts_with($image->url, $publicBase)) {
            $relativePath = ltrim(str_replace($publicBase, '', $image->url), '/'); // e.g. products/abc.jpg
        } else {
            // Si guardaste "/storage/..." o "storage/..."
            $relativePath = ltrim(str_replace(['storage/', '/storage/'], '', $image->url), '/');
        }

        // Borrar archivo físico si existe
        if ($relativePath && $disk->exists($relativePath)) {
            $disk->delete($relativePath);
        }

        $image->delete();

        return response()->json(['message' => 'Imagen eliminada correctamente']);
    }
}
