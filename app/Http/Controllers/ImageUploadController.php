<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class ImageUploadController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:4096', // 4MB
        ]);

        // Subir la imagen a Cloudinary
        $uploadedFileUrl = Cloudinary::upload(
            $request->file('image')->getRealPath(),
            ['folder' => 'marketplace/products'] // carpeta opcional
        )->getSecurePath();

        // Devolver la URL pública
        return response()->json([
            'url' => $uploadedFileUrl
        ]);
    }
}
