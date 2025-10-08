<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public function index()
    {
        $banners = Banner::all();
        return response()->json($banners);
    }

    public function show($id)
    {
        $banner = Banner::findOrFail($id);
        return response()->json($banner);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'nullable|string|max:100',
            'image' => 'required|string',
            'link' => 'nullable|string',
            'type' => 'required|string|in:MAIN,SMALL',
            'position' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $banner = Banner::create($validatedData);

        return response()->json($banner, 201);
    }

    public function update(Request $request, $id)
    {
        $banner = Banner::findOrFail($id);

        $validatedData = $request->validate([
            'title' => 'nullable|string|max:100',
            'image' => 'sometimes|string',
            'link' => 'nullable|string',
            'type' => 'sometimes|string|in:MAIN,SMALL',
            'position' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $banner->update($validatedData);

        return response()->json($banner);
    }

    public function destroy($id)
    {
        $banner = Banner::findOrFail($id);
        $banner->delete();

        return response()->json(null, 204);
    }
}