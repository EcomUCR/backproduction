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
    // Retrieve and return all contact messages.
    public function index()
    {
        $contactMessages = ContactMessage::all();
        return response()->json($contactMessages);
    }

    // Retrieve and return a specific contact message by its ID.
    public function show($id)
    {
        $contactMessage = ContactMessage::findOrFail($id);
        return response()->json($contactMessage);
    }

    // Store a new contact message and send an email notification.
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'nullable|string|max:80',
            'email' => 'nullable|string|email|max:120',
            'subject' => 'nullable|string|max:120',
            'message' => 'required|string',
            'read' => 'nullable|boolean',
        ]);

        // ✅ Guarda el mensaje
        $contactMessage = ContactMessage::create($validatedData);

        // ✅ Arma y envía el correo
        $subject = 'Nuevo mensaje desde el formulario de contacto';
        $to = env('MAIL_FROM_ADDRESS', 'ecomucr2025@gmail.com');
        $body = view('emails.contact', [
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'subject' => $validatedData['subject'] ?? '',
            'messageContent' => $validatedData['message'],
        ])->render();

        BrevoMailer::send($to, $subject, $body);

        // ✅ Notificar a todos los admins
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

    /**
     * 💬 Responder a un mensaje de contacto directamente desde el buzón.
     */
    public function reply(Request $request, $id)
    {
        $contactMessage = ContactMessage::findOrFail($id);

        $validated = $request->validate([
            'reply_message' => 'required|string|max:2000',
        ]);

        try {
            // Verifica que el mensaje tenga correo de origen
            if (!$contactMessage->email) {
                return response()->json([
                    'error' => 'El mensaje original no tiene un correo asociado para responder.',
                ], 400);
            }

            // Armar el correo de respuesta
            $subject = 'Re: ' . ($contactMessage->subject ?? 'Tu mensaje a TukiShop');
            $body = view('emails.contact-reply', [
                'userName' => $contactMessage->name ?? 'Usuario',
                'originalMessage' => $contactMessage->message,
                'replyMessage' => $validated['reply_message'],
                'companyName' => env('APP_NAME', 'TukiShop'),
            ])->render();

            // Enviar respuesta al remitente original
            BrevoMailer::send($contactMessage->email, $subject, $body);

            // Opcional: registrar en log o base de datos
            Log::info("📨 Respuesta enviada a mensaje de contacto ID {$contactMessage->id}");

            // Notificar al primer admin (opcional)
            $admin = User::where('role', 'ADMIN')->first();
            if ($admin) {
                Notification::create([
                    'user_id' => $admin->id,
                    'role' => 'ADMIN',
                    'type' => 'CONTACT_REPLY',
                    'title' => '📤 Respuesta enviada al contacto',
                    'message' => "Has respondido el mensaje de {$contactMessage->name} ({$contactMessage->email}).",
                    'related_id' => $contactMessage->id,
                    'related_type' => 'contact_message',
                    'is_read' => false,
                    'data' => [
                        'email' => $contactMessage->email,
                        'reply_message' => $validated['reply_message'],
                    ],
                ]);
            }

            return response()->json(['message' => 'Respuesta enviada correctamente.']);
        } catch (\Throwable $e) {
            Log::error("❌ Error al responder mensaje de contacto: {$e->getMessage()}");
            return response()->json(['error' => 'No se pudo enviar la respuesta.'], 500);
        }
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

    // Delete a contact message.
    public function destroy($id)
    {
        $contactMessage = ContactMessage::findOrFail($id);
        $contactMessage->delete();

        return response()->json(null, 204);
    }
}
