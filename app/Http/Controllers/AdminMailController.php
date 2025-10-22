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
        $validated = $request->validate([
            'store_name'   => 'required|string|max:100',
            'owner_name'   => 'required|string|max:80',
            'owner_email'  => 'required|email|max:120',
            'owner_phone'  => 'nullable|string|max:20',
            'request_date' => 'nullable|string',
            'admin_url'    => 'nullable|url'
        ]);

        // Buscar todos los administradores activos
        $admins = User::where('role', 'ADMIN')->get(['id', 'email', 'first_name', 'last_name']);

        if ($admins->isEmpty()) {
            return response()->json(['message' => 'No hay administradores para notificar'], 404);
        }

        // Datos dinámicos del correo
        $data = [
            'store_name'   => $validated['store_name'],
            'owner_name'   => $validated['owner_name'],
            'owner_email'  => $validated['owner_email'],
            'owner_phone'  => $validated['owner_phone'] ?? 'No especificado',
            'request_date' => $validated['request_date'] ?? now()->format('d/m/Y H:i'),
            'admin_url'    => $validated['admin_url'] ?? env('ADMIN_PANEL_URL', 'https://tukishopcr.com/admin/stores'),
        ];

        // Renderizar vista HTML del correo
        $html = view('emails.store_request', $data)->render();

        $subject = 'Nueva solicitud de verificación de tienda';

        // Enviar correo a todos los administradores
        foreach ($admins as $admin) {
            BrevoMailer::send($admin->email, $subject, $html);
        }

        return response()->json([
            'message' => 'Correo enviado exitosamente a los administradores.',
            'data' => $data,
            'recipients' => $admins->pluck('email')
        ], 200);
    }
}
