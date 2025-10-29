<?php

namespace App\Http\Controllers;

use App\Models\BannerImage;
use Illuminate\Http\Request;

class BannerImageController extends Controller
{
    /**
     * Listar todas las imágenes de banner
     */
    public function index()
    {
        $banners = BannerImage::latest()->get();
        return response()->json($banners);
    }

    /**
     * Crear una nueva imagen de banner
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'link' => 'required|string',
            'type' => 'in:CHARACTER,BACKGROUND',
            'alt_text' => 'nullable|string|max:150',
        ]);

        $banner = BannerImage::create($validated);

        return response()->json([
            'message' => 'Imagen de banner creada correctamente.',
            'banner' => $banner
        ], 201);
    }

    /**
     * Eliminar una imagen de banner
     */
    public function destroy($id)
    {
        $banner = BannerImage::findOrFail($id);
        $banner->delete();

        return response()->json(['message' => 'Imagen de banner eliminada correctamente.']);
    }
}
