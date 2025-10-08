<?php

namespace App\Http\Controllers;

use App\Models\StoreSocial;
use Illuminate\Http\Request;

class SocialMediaController extends Controller
{
    public function index()
    {
        $socialMedias = StoreSocial::all();
        return response()->json($socialMedias);
    }

    public function show($id)
    {
        $socialMedia = StoreSocial::findOrFail($id);
        return response()->json($socialMedia);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'platform' => 'required|string|max:50',
            'url' => 'required|string',
        ]);

        $socialMedia = StoreSocial::create($validatedData);

        return response()->json($socialMedia, 201);
    }

    public function update(Request $request, $id)
    {
        $socialMedia = StoreSocial::findOrFail($id);

        $validatedData = $request->validate([
            'store_id' => 'sometimes|exists:stores,id',
            'platform' => 'sometimes|string|max:50',
            'url' => 'sometimes|string',
        ]);

        $socialMedia->update($validatedData);

        return response()->json($socialMedia);
    }

    public function destroy($id)
    {
        $socialMedia = StoreSocial::findOrFail($id);
        $socialMedia->delete();

        return response()->json(null, 204);
    }
}