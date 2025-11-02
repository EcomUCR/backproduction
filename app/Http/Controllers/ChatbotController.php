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
            // 🧠 Paso 1: Clasificar intención general
            // 🧠 Paso 1: Clasificar intención general (incluye navegación)
            $intentResponse = $client->chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => "
Eres el asistente oficial de TukiShop.
Tu tarea es determinar la intención principal del mensaje del usuario.

Tipos posibles:
- \"chat\": saludo, charla o agradecimiento (ej. 'hola', 'cómo estás', 'gracias')
- \"search\": búsqueda de productos, categorías o artículos (ej. 'busco una prenda', 'tienen celulares?')
- \"navigate\": el usuario quiere ir a una sección de la app (carrito, perfil, vender, ayuda, etc.)

Devuelve SIEMPRE un JSON con formato:
{
  \"type\": \"chat\" | \"search\" | \"navigate\"
}
"
                    ],
                    ['role' => 'user', 'content' => $userMessage],
                ],
            ]);

            $intentText = $intentResponse->choices[0]->message->content ?? '{}';
            $intent = json_decode($intentText, true);
            $type = $intent['type'] ?? 'search';

            // 🔀 Enrutamiento según tipo
            if ($type === 'chat') {
                return $this->conversar($userMessage, $client);
            } elseif ($type === 'navigate') {
                return $this->navegar($userMessage, $client);
            }


            $intentText = $intentResponse->choices[0]->message->content ?? '{}';
            $intent = json_decode($intentText, true);
            $type = $intent['type'] ?? 'search';

            if ($type === 'chat') {
                return $this->conversar($userMessage, $client);
            }

            // 🧩 Paso 2: Detectar categorías y palabras clave
            $categoryAndKeywordPrompt = "
Eres un asistente de clasificación de productos para TukiShop.
Dada esta lista de categorías:

Arte, Automotriz, Belleza, Comida, Decoración, Deportes, Gaming, Herramientas, 
Hogar, Jardinería, Juegos, Juguetes, Libros, Limpieza, Mascotas, Música, 
Oficina, Ropa, Salud, Tecnología, Otros.

El usuario escribió: '{$userMessage}'.

Tu tarea:
1. Devuelve un JSON con:
   - \"categories\": hasta 4 categorías relevantes del listado anterior.
   - \"keywords\": hasta 6 palabras clave relevantes para buscar dentro de esas categorías.

Ejemplo de salida:
{
  \"categories\": [\"Ropa\", \"Moda\"],
  \"keywords\": [\"camisa\", \"blusa\", \"prenda\"]
}
";

            $extractResponse = $client->chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [['role' => 'user', 'content' => $categoryAndKeywordPrompt]],
            ]);

            $extractText = $extractResponse->choices[0]->message->content ?? '{}';
            $extractText = preg_replace('/^[^{]+|[^}]+$/', '', $extractText);
            $parsed = json_decode($extractText, true);

            $categories = $parsed['categories'] ?? [];
            $keywords = $parsed['keywords'] ?? [];
            if (str_contains(strtolower($userMessage), 'tienda') || str_contains(strtolower($userMessage), 'vendedor')) {
                return $this->buscarTiendas($userMessage, $client, $categories, $keywords);
            }

            return $this->buscarProductos($userMessage, $client, $categories, $keywords);

        } catch (\Throwable $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    private function buscarProductos(string $query, $client, array $categories = [], array $keywords = [])
    {
        // Limpieza básica
        $categories = array_filter($categories, fn($c) => strlen($c) > 1);
        $keywords = array_filter($keywords, fn($w) => strlen($w) > 2);

        // 🧩 Paso 1: obtener candidatos SQL combinando categoría + keywords
        $productsQuery = DB::table('products')
            ->join('stores', 'stores.id', '=', 'products.store_id')
            ->leftJoin('product_category', 'product_category.product_id', '=', 'products.id')
            ->leftJoin('categories', 'categories.id', '=', 'product_category.category_id')
            ->select(
                'products.id',
                'products.name',
                'products.description',
                'products.price',
                'products.discount_price',
                'products.image_1_url',
                'stores.name as store_name',
                DB::raw("COALESCE(categories.name, 'Sin categoría') as category_name")
            )
            ->whereRaw("TRIM(products.status) = 'ACTIVE'")
            ->whereRaw("TRIM(stores.status) = 'ACTIVE'")
            ->where('stores.is_verified', true);

        // 🧭 Si hay categorías detectadas, filtrarlas primero
        if (!empty($categories)) {
            $productsQuery->where(function ($q) use ($categories) {
                foreach ($categories as $cat) {
                    $q->orWhereRaw("LOWER(categories.name) LIKE ?", ["%" . strtolower($cat) . "%"]);
                }
            });
        }

        // 🧠 Luego, filtrar adicionalmente por las palabras clave (nombre/desc/detalle)
        if (!empty($keywords)) {
            $productsQuery->where(function ($q) use ($keywords) {
                foreach ($keywords as $word) {
                    $fuzzy = substr($word, -1) === 's' ? substr($word, 0, -1) : "{$word}s";
                    $q->orWhereRaw("LOWER(products.name) LIKE ?", ["%{$word}%"])
                        ->orWhereRaw("LOWER(products.name) LIKE ?", ["%{$fuzzy}%"])
                        ->orWhereRaw("LOWER(products.description) LIKE ?", ["%{$word}%"])
                        ->orWhereRaw("LOWER(products.description) LIKE ?", ["%{$fuzzy}%"])
                        ->orWhereRaw("LOWER(products.details) LIKE ?", ["%{$word}%"])
                        ->orWhereRaw("LOWER(products.details) LIKE ?", ["%{$fuzzy}%"]);
                }
            });
        }

        $candidates = $productsQuery->limit(12)->get();

        if ($candidates->isEmpty()) {
            return response()->json([
                'message' => "No encontré productos que coincidan con '{$query}'. ¿Querés intentar con otra palabra? 🛍️",
                'results' => [],
            ]);
        }

        // 🧠 Paso 2: Enviar a OpenAI para ranking semántico
        try {
            $candidateList = $candidates->map(function ($p) {
                return "{$p->id} - {$p->name} ({$p->store_name}) [Categoría: {$p->category_name}]";
            })->implode("\n");

            $rerankPrompt = "
Eres el asistente de búsqueda inteligente de TukiShop.
El usuario escribió: '{$query}'.
Categorías detectadas: " . implode(', ', $categories) . ".
Palabras clave: " . implode(', ', $keywords) . ".

Selecciona los 4 productos más relevantes de esta lista, priorizando coincidencias de categoría y relación semántica con la intención.
Evita mezclar categorías distintas.
Devuelve un JSON válido:
{
  \"selected_ids\": [lista con los IDs más relevantes, máximo 4]
}

Lista de productos:
{$candidateList}
";

            $rerankResponse = $client->chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [['role' => 'user', 'content' => $rerankPrompt]],
            ]);

            $json = $rerankResponse->choices[0]->message->content ?? '{}';
            $json = preg_replace('/^[^{]+|[^}]+$/', '', $json);
            $parsed = json_decode($json, true);
            $selectedIds = $parsed['selected_ids'] ?? [];

            $finalProducts = empty($selectedIds)
                ? $candidates->take(4)
                : $candidates->filter(fn($p) => in_array($p->id, $selectedIds))->take(4);

        } catch (\Throwable $e) {
            $finalProducts = $candidates->take(4);
        }

        // ✨ Paso 3: Respuesta cálida y breve
        try {
            $names = $finalProducts->pluck('name')->implode(', ');
            $prompt = "Eres el asistente de TukiShop. 
Responde de forma cálida y natural (máx. 2 líneas) sobre los resultados para '{$query}', 
mencionando algunos productos como {$names}.";

            $response = $client->chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [['role' => 'user', 'content' => $prompt]],
            ]);

            $message = $response->choices[0]->message->content ?? "Encontré varios productos relacionados con '{$query}' 😊";
        } catch (\Throwable $e) {
            $message = "Encontré varios productos relacionados con '{$query}' 😊";
        }

        return response()->json([
            'message' => $message,
            'results' => $finalProducts->values(),
        ]);
    }

    private function buscarTiendas(string $query, $client, array $categories = [], array $keywords = [])
    {
        $categories = array_values(array_filter($categories, fn($c) => is_string($c) && strlen($c) > 1));
        $keywords = array_values(array_filter($keywords, fn($w) => is_string($w) && strlen($w) > 2));

        // ---------- 1) Buscar TIENDAS directamente ----------
        // ---------- 1️⃣ Buscar TIENDAS por nombre, descripción o categoría ----------
        $storesQuery = DB::table('stores')
            ->leftJoin('store_categories', 'store_categories.id', '=', 'stores.category_id')
            ->select(
                'stores.id',
                'stores.name',
                'stores.description',
                'stores.image',
                'stores.banner',
                'stores.rating',
                DB::raw("COALESCE(store_categories.name, 'Sin categoría') AS category_name")
            )
            ->whereRaw("TRIM(stores.status) = 'ACTIVE'")
            ->where('stores.is_verified', true)
            ->where(function ($q) use ($categories, $keywords, $query) {
                $normalizedQuery = mb_strtolower(trim(preg_replace('/[^a-z0-9áéíóúüñ\s]/iu', '', $query)));

                // 🔹 1. Buscar por nombre o descripción usando las keywords
                foreach ($keywords as $word) {
                    $w = mb_strtolower(trim($word));
                    if (strlen($w) < 3)
                        continue;
                    $fuzzy = substr($w, -1) === 's' ? substr($w, 0, -1) : "{$w}s";
                    $q->orWhereRaw("LOWER(stores.name) LIKE ?", ["%{$w}%"])
                        ->orWhereRaw("LOWER(stores.name) LIKE ?", ["%{$fuzzy}%"])
                        ->orWhereRaw("LOWER(stores.description) LIKE ?", ["%{$w}%"])
                        ->orWhereRaw("LOWER(stores.description) LIKE ?", ["%{$fuzzy}%"]);
                }

                // 🔹 2. Buscar por categorías si existen
                foreach ($categories as $cat) {
                    $c = mb_strtolower(trim($cat));
                    $q->orWhereRaw("LOWER(store_categories.name) LIKE ?", ["%{$c}%"]);
                }

                // 🔹 3. Fallback: buscar cualquier palabra del mensaje original
                $words = array_filter(explode(' ', $normalizedQuery), fn($w) => strlen($w) > 2);
                foreach ($words as $w) {
                    $q->orWhereRaw("LOWER(stores.name) REGEXP ?", ["(^| ){$w}( |$)"])
                        ->orWhereRaw("LOWER(stores.description) REGEXP ?", ["(^| ){$w}( |$)"]);
                }
            });

        $foundStores = $storesQuery->limit(6)->get();

        if ($foundStores->isNotEmpty()) {
            // 🧠 Normalizar el texto de búsqueda
            $normalizedQuery = strtolower(trim(preg_replace('/[^a-z0-9áéíóúüñ\s]/iu', '', $query)));

            // 🔎 Buscar coincidencia fuerte por nombre exacto o parcial alto
            $exactMatch = $foundStores->first(function ($store) use ($normalizedQuery) {
                $storeName = strtolower(trim($store->name ?? ''));
                // Coincidencia exacta o muy similar
                return $storeName === $normalizedQuery ||
                    levenshtein($storeName, $normalizedQuery) <= 2 ||
                    str_contains($storeName, $normalizedQuery) ||
                    str_contains($normalizedQuery, $storeName);
            });

            if ($exactMatch) {
                // 🧩 Si hay coincidencia clara, solo devolver esa
                return response()->json([
                    'message' => "¡Perfecto! Encontré la tienda que buscabas 🏪",
                    'stores' => [$exactMatch],
                ]);
            }

            // 🧩 Si no hay coincidencia exacta, devuelve todas como sugerencias
            return response()->json([
                'message' => "Estas tiendas podrían interesarte 🏪",
                'stores' => $foundStores->values(),
            ]);
        }



        if (!empty($categories)) {
            $storesQuery->where(function ($q) use ($categories) {
                foreach ($categories as $cat) {
                    $q->orWhereRaw("LOWER(store_categories.name) LIKE ?", ['%' . strtolower($cat) . '%']);
                }
            });
        }

        if (!empty($keywords)) {
            $storesQuery->where(function ($q) use ($keywords) {
                foreach ($keywords as $w) {
                    $fuzzy = substr($w, -1) === 's' ? substr($w, 0, -1) : "{$w}s";
                    $q->orWhereRaw("LOWER(stores.name) LIKE ?", ["%{$w}%"])
                        ->orWhereRaw("LOWER(stores.name) LIKE ?", ["%{$fuzzy}%"])
                        ->orWhereRaw("LOWER(stores.description) LIKE ?", ["%{$w}%"])
                        ->orWhereRaw("LOWER(stores.description) LIKE ?", ["%{$fuzzy}%"]);
                }
            });
        }

        $foundStores = $storesQuery->limit(6)->get();

        if ($foundStores->isNotEmpty()) {
            return response()->json([
                'message' => "Estas tiendas podrían interesarte 🏪",
                'stores' => $foundStores->values(),
            ]);
        }

        // ---------- 2) Buscar PRODUCTOS para inferir TIENDAS ----------
        // Paso 2: buscar productos para inferir tiendas verificadas
        $productQuery = DB::table('products')
            ->join('stores', 'stores.id', '=', 'products.store_id')
            ->leftJoin('product_category', 'product_category.product_id', '=', 'products.id')
            ->leftJoin('categories', 'categories.id', '=', 'product_category.category_id')
            ->select(
                'stores.id AS store_id',
                'stores.name AS store_name',
                'stores.image AS store_image',
                'stores.banner AS store_banner',
                'stores.rating AS store_rating',
                DB::raw("COALESCE(categories.name, 'Sin categoría') AS product_category_name")
            )
            ->whereRaw("TRIM(products.status) = 'ACTIVE'")
            ->whereRaw("TRIM(stores.status) = 'ACTIVE'")
            ->where('stores.is_verified', true)
            ->where(function ($q) use ($categories, $keywords) {
                foreach (array_merge($categories, $keywords) as $w) {
                    $fuzzy = substr($w, -1) === 's' ? substr($w, 0, -1) : "{$w}s";
                    $q->orWhereRaw("LOWER(products.name) LIKE ?", ["%{$w}%"])
                        ->orWhereRaw("LOWER(products.description) LIKE ?", ["%{$w}%"])
                        ->orWhereRaw("LOWER(products.details) LIKE ?", ["%{$w}%"])
                        ->orWhereRaw("LOWER(categories.name) LIKE ?", ["%{$w}%"])
                        ->orWhereRaw("LOWER(stores.name) LIKE ?", ["%{$w}%"])
                        ->orWhereRaw("LOWER(products.name) LIKE ?", ["%{$fuzzy}%"])
                        ->orWhereRaw("LOWER(products.description) LIKE ?", ["%{$fuzzy}%"])
                        ->orWhereRaw("LOWER(products.details) LIKE ?", ["%{$fuzzy}%"]);
                }
            });


        // ✅ Filtro por categorías (sin excluir productos sin categoría)
        if (!empty($categories)) {
            $productQuery->where(function ($q) use ($categories) {
                foreach ($categories as $cat) {
                    $q->orWhereRaw("LOWER(categories.name) LIKE ?", ['%' . strtolower($cat) . '%'])
                        ->orWhereRaw("LOWER(products.name) LIKE ?", ['%' . strtolower($cat) . '%'])
                        ->orWhereRaw("LOWER(products.description) LIKE ?", ['%' . strtolower($cat) . '%']);
                }
            });
        }

        // ✅ Filtro por keywords (nombre, descripción y tienda relacionada)
        if (!empty($keywords)) {
            $productQuery->where(function ($q) use ($keywords) {
                foreach ($keywords as $w) {
                    $fuzzy = substr($w, -1) === 's' ? substr($w, 0, -1) : "{$w}s";
                    $q->orWhereRaw("LOWER(products.name) LIKE ?", ["%{$w}%"])
                        ->orWhereRaw("LOWER(products.description) LIKE ?", ["%{$w}%"])
                        ->orWhereRaw("LOWER(products.details) LIKE ?", ["%{$w}%"])
                        // 🧠 Extra: también busca por el nombre de la tienda
                        ->orWhereRaw("LOWER(stores.name) LIKE ?", ["%{$w}%"]);
                }
            });
        }

        $productCandidates = $productQuery->limit(10)->get();

        if ($productCandidates->isNotEmpty()) {
            $storesFromProducts = $this->uniqueStoresFromProducts($productCandidates)->take(2)->values();

            if ($storesFromProducts->isNotEmpty()) {
                return response()->json([
                    'message' => "No encontré tiendas directas, pero estas venden productos relacionados 🐾",
                    'stores' => $storesFromProducts,
                ]);
            }
        }

        // ---------- 3) Fallback ----------
        return response()->json([
            'message' => "No encontré tiendas para esa temática. Te llevo al listado general de tiendas para que explores. 🙏",
            'stores' => [],
            'link' => '/search/stores',
        ]);
    }


    private function uniqueStoresFromProducts($productRows)
    {
        // $productRows: colección con campos store_id, store_name, store_image, store_banner, store_rating
        $seen = [];
        $unique = [];

        foreach ($productRows as $row) {
            if (!isset($seen[$row->store_id])) {
                $seen[$row->store_id] = true;

                $unique[] = (object) [
                    'id' => $row->store_id,
                    'name' => $row->store_name,
                    'image' => $row->store_image,
                    'banner' => $row->store_banner,
                    'rating' => $row->store_rating,
                    // opcional: 'category_name' => $row->product_category_name,
                ];
            }
        }

        return collect($unique);
    }


    private function navegar(string $userMessage, $client)
    {
        $routes = [
            'inicio' => '/',
            'home' => '/',
            'ayuda' => '/help',
            'carrito' => '/shoppingCart',
            'wishlist' => '/wishlist',
            'favoritos' => '/wishlist',
            'perfil' => '/profile',
            'cuenta' => '/profile',
            'vender' => '/beSellerPage',
            'tienda' => '/search/stores',
            'mis ordenes' => '/profile',
            'soporte' => '/help',
            'contacto' => '/contact',
            'problema' => '/reportProblem',
        ];

        // 🔍 Paso 1: detectar la sección solicitada
        $prompt = "
Eres el asistente de TukiShop.
El usuario escribió: '{$userMessage}'.
De la lista de secciones disponibles, elige a cuál debería referirse o ser redirigido.

Lista de secciones:
" . implode(", ", array_keys($routes)) . "

Devuelve un JSON con formato:
{
  \"section\": \"una de las anteriores o null si no aplica\"
}
";

        $navResponse = $client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [['role' => 'user', 'content' => $prompt]],
        ]);

        $json = $navResponse->choices[0]->message->content ?? '{}';
        $json = preg_replace('/^[^{]+|[^}]+$/', '', $json);
        $parsed = json_decode($json, true);
        $section = strtolower($parsed['section'] ?? '');
        $link = $routes[$section] ?? null;

        if (!$link) {
            return response()->json([
                'message' => "Parece que querés navegar en TukiShop, pero no estoy seguro de a dónde. 😊 ¿Podrías aclararme un poco más?",
                'results' => [],
                'navigate' => false,
            ]);
        }

        // 🧠 Paso 2: Determinar si el usuario quiere navegar o solo preguntar
        $intentPrompt = "
Analiza este mensaje del usuario: '{$userMessage}'.
¿Está pidiendo explícitamente ir o navegar a esa sección (por ejemplo, 'llevame', 'quiero ir', 'abrir', 'entrar', 'muéstrame')?
Si solo pregunta dónde está o cómo acceder, responde que NO.

Devuelve un JSON:
{
  \"navigate\": true | false
}
";

        $intentResponse = $client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [['role' => 'user', 'content' => $intentPrompt]],
        ]);

        $intentJson = $intentResponse->choices[0]->message->content ?? '{}';
        $intentJson = preg_replace('/^[^{]+|[^}]+$/', '', $intentJson);
        $intentParsed = json_decode($intentJson, true);
        $shouldNavigate = $intentParsed['navigate'] ?? false;

        // 🗣️ Mensaje amigable
        $promptMsg = "
Eres el asistente de TukiShop.
Responde con un texto breve (1–2 líneas) explicando que puede acceder a la sección '{$section}'.
Ejemplo: '¡Perfecto! Aquí podés ver tus productos favoritos ❤️' o 'Para vender en TukiShop, ingresá aquí 👇'.
";

        $msgResponse = $client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [['role' => 'user', 'content' => $promptMsg]],
        ]);

        $message = $msgResponse->choices[0]->message->content ?? "Aquí tenés el enlace que buscabas 👇";

        // 🚀 Devolver respuesta adaptada
        return response()->json([
            'message' => $message,
            'link' => $link,
            'results' => [],
            'navigate' => (bool) $shouldNavigate, // 👈 Nuevo campo
        ]);
    }




    // ============================================================
    // 💬 Conversación breve y natural
    // ============================================================
    private function conversar(string $userMessage, $client)
    {
        try {
            $prompt = "
Eres el asistente de TukiShop. 
Habla con el usuario de forma corta, alegre y natural (máximo 2 líneas). 
Usa emojis moderadamente y evita sonar robótico o muy formal.
El usuario dijo: '{$userMessage}'.";

            $response = $client->chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [['role' => 'user', 'content' => $prompt]],
            ]);

            $message = $response->choices[0]->message->content ?? "¡Hola! 😊 ¿En qué puedo ayudarte hoy?";

            return response()->json([
                'message' => $message,
                'results' => [],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => "¡Hola! 😊 ¿Cómo estás? ¿Querés que te ayude a buscar algo?",
                'results' => [],
            ]);
        }
    }
}
