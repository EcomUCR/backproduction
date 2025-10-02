<?php

namespace App\Http\Controllers;

use App\Models\StoreSocial;
use Illuminate\Http\Request;

class StoreSocialController extends Controller
{
    public function index()
    {
        $storeSocials = StoreSocial::all();
        return response()->json($storeSocials);
    }

    public function show($id)
    {
        $storeSocial = StoreSocial::findOrFail($id);
        return response()->json($storeSocial);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'platform' => 'required|string|max:50',
            'url' => 'required|string',
        ]);

        $storeSocial = StoreSocial::create($validatedData);

        return response()->json($storeSocial, 201);
    }

    public function update(Request $request, $id)
    {
        $storeSocial = StoreSocial::findOrFail($id);

        $validatedData = $request->validate([
            'store_id' => 'sometimes|exists:stores,id',
            'platform' => 'sometimes|string|max:50',
            'url' => 'sometimes|string',
        ]);

        $storeSocial->update($validatedData);

        return response()->json($storeSocial);
    }

    public function destroy($id)
    {
        $storeSocial = StoreSocial::findOrFail($id);
        $storeSocial->delete();

        return response()->json(null, 204);
    }
}