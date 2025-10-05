<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OpenAI\Client;

class OpenAIController extends Controller
{
    public function generateDescription(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $productName = $request->product_name;

        $client = \OpenAI::client(env('OPENAI_API_KEY'));


        $response = $client->chat()->create([
            'model' => 'gpt-4.1-mini',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => "Escribe una descripción creativa y detallada para el producto: $productName en español, menos de 200 palabras.",
                ],
            ],
        ]);

        $description = $response->choices[0]->message->content ?? '';

        return response()->json([
            'description' => $description,
        ]);
    }
}
