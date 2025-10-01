<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;

class ProductImageController extends Controller
{
    private function publicUrl(string $path): string
    {
        // Para bucket público en Supabase:
        // {SUPABASE_PROJECT_URL}/storage/v1/object/public/{bucket}/{path}
        $base   = rtrim(env('SUPABASE_PROJECT_URL'), '/');
        $bucket = env('SUPABASE_PUBLIC_BUCKET');
        $path   = ltrim($path, '/');

        return "{$base}/storage/v1/object/public/{$bucket}/{$path}";
    }

    /**
     * Listar imágenes de un producto
     */
    public function index($productId): JsonResponse
    {
        $product = Product::with('images')->findOrFail($productId);

        // Ya guardamos URL pública en BD, no hay que convertir nada
        return response()->json([
            'product' => $product->name,
            'images'  => $product->images,
        ]);
    }

    /**
     * Guardar nueva imagen de un producto (Supabase Storage - S3)
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'image'      => 'required|image|mimes:jpg,jpeg,png,webp,avif|max:4096',
        ]);

        $disk = Storage::disk('s3');

        // Carpeta por producto: products/{product_id}/YYYY/MM/
        $subdir   = 'products/' . $request->product_id . '/' . date('Y/m');
        $filename = uniqid('img_', true) . '.' . $request->file('image')->getClientOriginalExtension();
        $path     = $subdir . '/' . $filename;

        // Sube a Supabase S3
        $disk->put($path, file_get_contents($request->file('image')->getRealPath()), [
            'visibility'   => 'public', // si el bucket es público
            'CacheControl' => 'public, max-age=31536000, immutable',
        ]);

        // Construye URL pública
        $publicUrl = $this->publicUrl($path);

        // order = max + 1
        $nextOrder = (int) ProductImage::where('product_id', $request->product_id)->max('order') + 1;

        $image = ProductImage::create([
            'product_id' => $request->product_id,
            'url'        => $publicUrl,
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

        // Convertir URL pública a path relativo del bucket
        $bucket = env('SUPABASE_PUBLIC_BUCKET');
        $prefix = rtrim(env('SUPABASE_PROJECT_URL'), '/') . '/storage/v1/object/public/' . $bucket . '/';

        $path = $image->url;
        if (str_starts_with($path, $prefix)) {
            $path = ltrim(substr($path, strlen($prefix)), '/'); // queda solo {path} relativo
        }

        $disk = Storage::disk('s3');

        if ($path && $disk->exists($path)) {
            $disk->delete($path);
        }

        $image->delete();

        return response()->json(['message' => 'Imagen eliminada correctamente']);
    }
}
