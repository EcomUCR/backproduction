<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\Request;
use App\Services\BrevoMailer;
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

        // Guarda en la base de datos (opcional)
        $contactMessage = ContactMessage::create($validatedData);

        // Arma el correo
        $subject = 'Nuevo mensaje desde el formulario de contacto';
        $to = env('MAIL_FROM_ADDRESS', 'ecomucr2025@gmail.com');
        $body = view('emails.contact', [
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'subject' => $validatedData['subject'] ?? '',
            'messageContent' => $validatedData['message']
        ])->render();

        // ENVÍA EL CORREO
        BrevoMailer::send($to, $subject, $body);

        return response()->json($contactMessage, 201);
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