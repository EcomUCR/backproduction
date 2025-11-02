<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenAI;

class ChatbotController extends Controller
{
    public function handle(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:500',
        ]);

        $userMessage = trim($request->input('message'));
        $client = \OpenAI::client(env('OPENAI_API_KEY'));

        try {
            // 🧠 Paso 1. Detectar intención principal
            $response = $client->chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => "
Eres el asistente oficial de TukiShop. 
Tu personalidad es natural, amigable, con tono cercano (como un amigo que ayuda a comprar).
Tu tarea es analizar el mensaje y decidir una acción, devolviendo SIEMPRE un JSON válido:
{
  \"action\": \"buscar_productos\" | \"info_plataforma\" | \"enlaces\" | \"conversacion\" | \"sin_respuesta\",
  \"query\": \"texto o palabra clave\"
}
Si el usuario solo saluda o hace una pregunta informal (ej. 'hola', 'qué tal', 'cómo estás'),
responde con {\"action\": \"conversacion\", \"query\": \"saludo\"}.
",
                    ],
                    ['role' => 'user', 'content' => $userMessage],
                ],
            ]);

            $content = $response->choices[0]->message->content ?? '{}';
            $intent = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $content = preg_replace('/^[^{]+|[^}]+$/', '', $content);
                $intent = json_decode($content, true);
            }

            $action = $intent['action'] ?? 'sin_respuesta';
            $query = $intent['query'] ?? '';

            // 🔀 Dirigir según intención detectada
            return match ($action) {
                'buscar_productos' => $this->buscarProductos($query, $client),
                'info_plataforma' => $this->infoPlataforma($query, $client),
                'enlaces' => $this->enlacesRelacionados($query, $client),
                'conversacion' => $this->respuestaConversacional($query, $userMessage, $client),
                default => $this->respuestaGenerica($userMessage, $client),
            };
        } catch (\Throwable $e) {
            \Log::error('❌ Error en ChatbotController', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return response()->json([
                'message' => 'Error interno en el chatbot.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================================
    // 🗣️ Conversación natural (saludos, charla)
    // ============================================================
    private function respuestaConversacional(string $query, string $userMessage, $client)
    {
        $prompt = "
Eres el asistente de TukiShop, cálido, natural y simpático.
Responde al usuario de forma fluida, amistosa y breve. 
Si el usuario te saluda, respóndele con un saludo amistoso y ofrece ayuda (por ejemplo: '¡Hola! 😊 ¿En qué puedo ayudarte hoy?').
No des respuestas genéricas, suena humano, no tan formal.
Usuario dijo: '{$userMessage}'.
";

        $response = $client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [['role' => 'user', 'content' => $prompt]],
        ]);

        return response()->json([
            'message' => $response->choices[0]->message->content ?? "¡Hola! 😊 ¿En qué puedo ayudarte hoy?",
            'results' => [],
        ]);
    }

    // ============================================================
    // 🔍 1. Buscar productos (igual que antes)
    // ============================================================
    private function buscarProductos(string $query, $client)
    {
        $query = trim($query);
        $keywords = preg_split('/\s+/', strtolower($query));

        $products = DB::table('products')
            ->join('stores', 'stores.id', '=', 'products.store_id')
            ->leftJoin('product_category', 'products.id', '=', 'product_category.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'product_category.category_id')
            ->select(
                'products.id',
                'products.name',
                'products.description',
                'products.price',
                'products.discount_price',
                'products.image_1_url',
                'stores.name as store_name',
                DB::raw("COALESCE(categories.name::text, 'Sin categoría') as category_name")
            )
            ->where('products.status', 'ACTIVE')
            ->where('stores.status', 'ACTIVE')
            ->whereRaw('stores.is_verified = true::boolean')
            ->where(function ($q) use ($keywords) {
                foreach ($keywords as $word) {
                    $q->orWhereRaw("LOWER(products.name) ILIKE ?", ["%{$word}%"])
                        ->orWhereRaw("LOWER(products.description) ILIKE ?", ["%{$word}%"])
                        ->orWhereRaw("LOWER(products.details) ILIKE ?", ["%{$word}%"])
                        ->orWhereRaw("LOWER(categories.name) ILIKE ?", ["%{$word}%"])
                        ->orWhereRaw("LOWER(stores.name) ILIKE ?", ["%{$word}%"]);
                }
            })
            ->limit(10)
            ->get();

        if ($products->isEmpty()) {
            $simplified = substr($query, 0, 6);
            $fallback = DB::table('products')
                ->join('stores', 'stores.id', '=', 'products.store_id')
                ->select(
                    'products.id',
                    'products.name',
                    'products.image_1_url',
                    'products.price',
                    'products.discount_price',
                    'stores.name as store_name'
                )
                ->where('products.status', 'ACTIVE')
                ->where('stores.status', 'ACTIVE')
                ->whereRaw('stores.is_verified = true::boolean')
                ->where(function ($q) use ($simplified) {
                    $q->whereRaw("LOWER(products.name) ILIKE ?", ["%{$simplified}%"])
                        ->orWhereRaw("LOWER(products.description) ILIKE ?", ["%{$simplified}%"]);
                })
                ->limit(10)
                ->get();

            if ($fallback->isEmpty()) {
                return response()->json([
                    'message' => "No encontré resultados exactos para '{$query}', pero puedo ayudarte a buscar algo similar.",
                    'results' => [],
                ]);
            }

            $products = $fallback;
        }

        $names = $products->pluck('name')->take(3)->implode(', ');
        $prompt = "Eres el asistente de TukiShop. 
Genera una respuesta natural, cálida y breve (máximo 2 líneas) para el usuario que buscó '{$query}'.
Menciona de forma fluida algunos productos como {$names}, 
pero sin dar descripciones largas ni muchos detalles. 
Evita repetir ideas o sonar exagerado.";

        $response = $client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [['role' => 'user', 'content' => $prompt]],
        ]);

        $message = $response->choices[0]->message->content ?? "Encontré algunos productos relacionados con '{$query}'.";

        return response()->json([
            'message' => $message,
            'results' => $products,
        ]);
    }



    // ============================================================
    // 📘 2. Info plataforma (igual que antes)
    // ============================================================
    private function infoPlataforma(string $query, $client)
    {
        $infos = [
            'envío' => 'Envíos en todo el país dentro de 1 a 3 días hábiles.',
            'pago' => 'Pagos con Visa, Mastercard y Stripe.',
            'devolución' => 'Devoluciones dentro de los primeros 15 días.',
            'cuenta' => 'Puedes crear tu cuenta para guardar direcciones y pedidos.',
            'tienda' => 'Puedes crear tu propia tienda y vender productos.',
        ];

        $found = collect($infos)->first(function ($_, $key) use ($query) {
            return str_contains($query, $key);
        });

        $prompt = $found
            ? "Explícalo de forma natural y cercana, como si hablaras con un cliente: {$found}"
            : "Responde naturalmente explicando las funciones principales de TukiShop.";

        $response = $client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [['role' => 'user', 'content' => $prompt]],
        ]);

        return response()->json([
            'message' => $response->choices[0]->message->content ?? "TukiShop te ayuda a comprar y vender productos fácilmente 😊",
        ]);
    }

    // ============================================================
    // 🔗 3. Enlaces naturales
    // ============================================================
    private function enlacesRelacionados(string $query, $client)
    {
        $links = [
            'camisas' => '/products?category=camisas',
            'ofertas' => '/offers',
            'contacto' => '/contact',
            'ayuda' => '/help',
            'tienda' => '/stores',
            'inicio' => '/',
        ];

        foreach ($links as $key => $url) {
            if (str_contains($query, $key)) {
                $prompt = "Dile al usuario de forma amistosa que puede visitar este enlace relacionado con '{$key}': {$url}";
                $response = $client->chat()->create([
                    'model' => 'gpt-4o-mini',
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                ]);

                return response()->json([
                    'message' => $response->choices[0]->message->content ?? "Podés visitar este enlace: {$url}",
                    'link' => $url,
                ]);
            }
        }

        return response()->json([
            'message' => "No encontré un enlace directo, pero podés visitar /help para más información.",
        ]);
    }

    // ============================================================
    // 🤷 4. Fallback (más natural)
    // ============================================================
    private function respuestaGenerica(string $userMessage, $client)
    {
        $prompt = "
Eres el asistente de TukiShop.
El usuario dijo: '{$userMessage}'.
No entendiste la intención, pero en vez de decir 'no entendí', responde de forma amable, 
intentando mantener la conversación. Por ejemplo:
- 'Mmm, no estoy seguro a qué te referís, ¿podrías contarme un poco más?'
- 'Interesante 😄, ¿me podrías dar más detalles para ayudarte mejor?'
Usa un tono cercano, simpático y natural.
";

        $response = $client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [['role' => 'user', 'content' => $prompt]],
        ]);

        return response()->json([
            'message' => $response->choices[0]->message->content ??
                "No estoy del todo seguro de a qué te referís 😅, ¿podrías contarme un poco más?",
            'results' => [],
        ]);
    }
}
