<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Services\BrevoMailer;

class AdminMailController extends Controller
{
    /**
     * Envía un correo HTML a todos los administradores
     * cuando se crea una nueva tienda.
     */
    public function sendStoreVerificationEmail(Request $request)
    {
        $validatedData = $request->validate([
            'store_name'   => 'required|string|max:100',
            'owner_name'   => 'required|string|max:80',
            'owner_email'  => 'required|email|max:120',
            'owner_phone'  => 'nullable|string|max:20',
            'request_date' => 'nullable|string',
            'admin_url'    => 'nullable|url'
        ]);

        // Buscar administradores activos
        $admins = User::where('role', 'ADMIN')->get(['email']);

        if ($admins->isEmpty()) {
            return response()->json(['message' => 'No hay administradores para notificar'], 404);
        }

        // Armar el correo igual que el contact controller
        $subject = 'Nueva solicitud de verificación de tienda';
        $body = view('emails.verification-request', [
            'store_name'   => $validatedData['store_name'],
            'owner_name'   => $validatedData['owner_name'],
            'owner_email'  => $validatedData['owner_email'],
            'owner_phone'  => $validatedData['owner_phone'] ?? 'No especificado',
            'request_date' => $validatedData['request_date'] ?? now()->format('d/m/Y H:i'),
            'admin_url'    => $validatedData['admin_url'] ?? env('ADMIN_PANEL_URL', 'https://tukishopcr.com/admin/stores'),
        ])->render();

        // Enviar el correo a todos los admins (uno por uno, como en ContactController)
        foreach ($admins as $admin) {
            BrevoMailer::send(
                $admin->email,
                $subject,
                $body
            );
        }

        return response()->json([
            'message' => 'Correo enviado exitosamente a los administradores.',
            'recipients' => $admins->pluck('email'),
        ], 201);
    }
}
