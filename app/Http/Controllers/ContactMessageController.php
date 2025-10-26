<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\BrevoMailer;
use Illuminate\Support\Facades\Log;

class ContactMessageController extends Controller
{
    public function index()
    {
        $contactMessages = ContactMessage::all();
        return response()->json($contactMessages);
    }

    public function show($id)
    {
        $contactMessage = ContactMessage::findOrFail($id);
        return response()->json($contactMessage);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'nullable|string|max:80',
            'email' => 'nullable|string|email|max:120',
            'subject' => 'nullable|string|max:120',
            'message' => 'required|string',
            'read' => 'nullable|boolean',
        ]);

        // ✅ Guarda el mensaje en la base de datos
        $contactMessage = ContactMessage::create($validatedData);

        // ✅ Arma el correo
        $subject = 'Nuevo mensaje desde el formulario de contacto';
        $to = env('MAIL_FROM_ADDRESS', 'ecomucr2025@gmail.com');

        $body = view('emails.contact', [
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'subject' => $validatedData['subject'] ?? '',
            'messageContent' => $validatedData['message'],
        ])->render();

        // ✅ Envía el correo
        BrevoMailer::send($to, $subject, $body);

        // ✅ Envía notificación a todos los administradores
        try {
            $admins = User::where('role', 'ADMIN')->get();

            foreach ($admins as $admin) {
                Notification::create([
                    'user_id' => $admin->id,
                    'role' => 'ADMIN',
                    'type' => 'CONTACT_MESSAGE',
                    'title' => '📩 Nuevo mensaje de contacto',
                    'message' => 'Has recibido un nuevo mensaje desde el formulario de contacto.',
                    'related_id' => $contactMessage->id,
                    'related_type' => 'contact_message',
                    'priority' => 'NORMAL',
                    'is_read' => false,
                    'data' => [
                        'name' => $validatedData['name'] ?? 'Anónimo',
                        'email' => $validatedData['email'] ?? 'No especificado',
                        'subject' => $validatedData['subject'] ?? 'Sin asunto',
                        'message' => $validatedData['message'],
                        'contact_id' => $contactMessage->id,
                    ],
                ]);
            }
        } catch (\Exception $e) {
            Log::error('❌ Error al crear notificaciones para admins: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Mensaje enviado y notificaciones creadas correctamente.',
            'contact_message' => $contactMessage,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $contactMessage = ContactMessage::findOrFail($id);

        $validatedData = $request->validate([
            'name' => 'nullable|string|max:80',
            'email' => 'nullable|string|email|max:120',
            'subject' => 'nullable|string|max:120',
            'message' => 'sometimes|string',
            'read' => 'nullable|boolean',
        ]);

        $contactMessage->update($validatedData);

        return response()->json($contactMessage);
    }

    public function destroy($id)
    {
        $contactMessage = ContactMessage::findOrFail($id);
        $contactMessage->delete();

        return response()->json(null, 204);
    }
}
