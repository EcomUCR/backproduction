<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Cloudinary\Cloudinary;

class ImageUploadController extends Controller
{
    // Handles image uploads to Cloudinary and returns the URL.
    public function upload(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:4096',
        ]);
        
        $cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => env('CLOUDINARY_CLOUD_NAME', 'dpbghs8ep'),
                'api_key'    => env('CLOUDINARY_API_KEY', '487757492116512'),
                'api_secret' => env('CLOUDINARY_API_SECRET', 'uiPutUcwrquKbm7ooeRuYL6szAs'),
            ],
        ]);

        $upload = $cloudinary->uploadApi()->upload($request->file('image')->getRealPath(), [
            'folder' => 'marketplace/products'
        ]);

        return response()->json([
            'url' => $upload['secure_url'],
        ]);
    }
}