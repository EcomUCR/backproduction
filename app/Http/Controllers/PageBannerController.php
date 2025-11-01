<?php

namespace App\Http\Controllers;

use App\Models\PageBanner;
use App\Models\Banner;
use Illuminate\Http\Request;

class PageBannerController extends Controller
{
    /**
     * Mostrar todos los page banners.
     */
    public function index()
    {
        $pageBanners = PageBanner::with('banner')->get();
        return response()->json($pageBanners);
    }

    /**
     * Crear un nuevo page banner.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'page_name' => 'required|string|max:50',
            'slot_number' => 'required|integer|min:1|max:2',
            'banner_id' => 'required|exists:banners,id',
        ]);

        // Evitar duplicados (una misma página no puede tener el mismo slot repetido)
        $exists = PageBanner::where('page_name', $validated['page_name'])
            ->where('slot_number', $validated['slot_number'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Ya existe un banner asignado a ese slot en la página.',
            ], 422);
        }

        $pageBanner = PageBanner::create($validated);
        return response()->json($pageBanner, 201);
    }

    /**
     * Mostrar un page banner específico.
     */
    public function show($id)
    {
        $pageBanner = PageBanner::with('banner')->find($id);

        if (!$pageBanner) {
            return response()->json(['message' => 'PageBanner no encontrado'], 404);
        }

        return response()->json($pageBanner);
    }

    /**
     * Actualizar un page banner existente.
     */
    public function update(Request $request, $id)
    {
        $pageBanner = PageBanner::find($id);

        if (!$pageBanner) {
            return response()->json(['message' => 'PageBanner no encontrado'], 404);
        }

        $validated = $request->validate([
            'page_name' => 'sometimes|string|max:50',
            'slot_number' => 'sometimes|integer|min:1|max:2',
            'banner_id' => 'sometimes|exists:banners,id',
        ]);

        $pageBanner->update($validated);
        return response()->json($pageBanner);
    }

    /**
     * Eliminar un page banner.
     */
    public function destroy($id)
    {
        $pageBanner = PageBanner::find($id);

        if (!$pageBanner) {
            return response()->json(['message' => 'PageBanner no encontrado'], 404);
        }

        $pageBanner->delete();

        return response()->json(['message' => 'PageBanner eliminado correctamente']);
    }
}
