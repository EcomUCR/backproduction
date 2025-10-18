<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    /**
     * Mostrar todos los banners.
     */
    public function index()
    {
        $banners = Banner::all();
        return response()->json($banners);
    }

    /**
     * Mostrar un banner específico.
     */
    public function show($id)
    {
        $banner = Banner::findOrFail($id);
        return response()->json($banner);
    }

    /**
     * Crear un nuevo banner.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'nullable|string|max:100',
            'subtitle' => 'nullable|string',
            'character' => 'nullable|string',
            'image' => 'required|string',
            'link' => 'nullable|string',
            'btn_text' => 'nullable|string|max:50',
            'btn_color' => 'nullable|string|in:MORADO,AMARILLO,NARANJA,GRADIENTE',
            'type' => 'required|string|in:LARGE,SHORT,SLIDER',
            'orientation' => 'nullable|string|in:LEFT,RIGTH',
            'position' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $banner = Banner::create($validatedData);

        return response()->json($banner, 201);
    }

    /**
     * Actualizar un banner existente.
     */
    public function update(Request $request, $id)
    {
        $banner = Banner::findOrFail($id);

        $validatedData = $request->validate([
            'title' => 'nullable|string|max:100',
            'subtitle' => 'nullable|string',
            'character' => 'nullable|string',
            'image' => 'sometimes|string',
            'link' => 'nullable|string',
            'btn_text' => 'nullable|string|max:50',
            'btn_color' => 'nullable|string|in:MORADO,AMARILLO,NARANJA,GRADIENTE',
            'type' => 'sometimes|string|in:LARGE,SHORT,SLIDER',
            'orientation' => 'nullable|string|in:LEFT,RIGTH',
            'position' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $banner->update($validatedData);

        return response()->json($banner);
    }

    /**
     * Eliminar un banner.
     */
    public function destroy($id)
    {
        $banner = Banner::findOrFail($id);
        $banner->delete();

        return response()->json(null, 204);
    }
}
