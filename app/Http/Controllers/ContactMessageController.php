<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\Request;
use App\Services\BrevoMailer;
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
        
        $contactMessage = ContactMessage::create($validatedData);
        $subject = 'Nuevo mensaje desde el formulario de contacto';
        $to = env('MAIL_FROM_ADDRESS', 'ecomucr2025@gmail.com');
        $body = view('emails.contact', [
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'subject' => $validatedData['subject'] ?? '',
            'messageContent' => $validatedData['message']
        ])->render();

        BrevoMailer::send($to, $subject, $body);

        return response()->json($contactMessage, 201);
    }

    // Update a contact message.
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