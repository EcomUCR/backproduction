<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\User;
use App\Models\Notification;
use App\Services\BrevoMailer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    /**
     * Listar todos los reportes
     */
    public function index()
    {
        $reports = Report::with('order')->latest()->get();
        return response()->json($reports);
    }

    /**
     * Crear un nuevo reporte y notificar a los administradores
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'order_id' => 'nullable|exists:orders,id',
                'name' => 'required|string|max:100',
                'email' => 'required|email|max:120',
                'subject' => 'nullable|string|max:120',
                'description' => 'required|string',
                'images' => 'nullable|array',     // Array de URLs
                'images.*' => 'string',           // Cada una es una URL (Cloudinary)
            ]);

            // 🧮 Generar número único de reporte
            $lastId = Report::max('id') ?? 0;
            $nextNumber = str_pad($lastId + 1, 5, '0', STR_PAD_LEFT);
            $reportNumber = 'REP-' . $nextNumber;

            // 💾 Crear el reporte
            $report = Report::create([
                ...$validated,
                'report_number' => $reportNumber,
            ]);

            // 📨 Notificar a los administradores
            try {
                $admins = User::where('role', 'ADMIN')->get(['id', 'email', 'username']);

                foreach ($admins as $admin) {
                    // 🔔 Crear notificación interna
                    $messageSubject = $report->subject ? $report->subject : 'Sin asunto';

                    Notification::create([
                        'user_id' => $admin->id,
                        'role' => 'ADMIN',
                        'type' => 'NEW_REPORT',
                        'title' => '🧾 Nuevo reporte recibido',
                        'message' => "Se ha recibido un nuevo reporte de {$report->name}: “{$messageSubject}”.",
                        'related_id' => $report->id,
                        'related_type' => 'report',
                        'priority' => 'HIGH',
                        'is_read' => false,
                        'data' => [
                            'report_id' => $report->id,
                            'report_number' => $report->report_number,
                            'user_name' => $report->name,
                            'email' => $report->email,
                        ],
                    ]);
                }

                // 📧 Enviar correo a los administradores
                if ($admins->isNotEmpty()) {
                    $subject = '🧾 Nuevo reporte recibido | TukiShop';
                    $body = view('emails.admin_new_report', [
                        'report' => $report,
                        'adminPanelUrl' => env('ADMIN_PANEL_URL', 'https://tukishop.vercel.app/admin/reports'),
                    ])->render();

                    foreach ($admins as $admin) {
                        BrevoMailer::send($admin->email, $subject, $body);
                    }
                }
            } catch (\Exception $e) {
                \Log::error('❌ Error al enviar notificación/correo de nuevo reporte: ' . $e->getMessage());
            }

            return response()->json([
                'message' => 'Reporte creado correctamente y administradores notificados.',
                'report' => $report,
            ], 201);
        } catch (\Throwable $e) {
            \Log::error('❌ Error al crear reporte: ' . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mostrar un reporte específico
     */
    public function show($id)
    {
        $report = Report::with('order')->findOrFail($id);
        return response()->json($report);
    }

    /**
     * Actualizar el estado o notas de un reporte
     */
    public function update(Request $request, $id)
    {
        $report = Report::findOrFail($id);

        $validated = $request->validate([
            'status' => 'in:PENDING,IN_REVIEW,RESOLVED,REJECTED',
            'admin_notes' => 'nullable|string',
            'read' => 'boolean',
        ]);

        $report->update($validated);

        return response()->json([
            'message' => 'Reporte actualizado correctamente.',
            'report' => $report
        ]);
    }

    /**
     * Eliminar un reporte
     */
    public function destroy($id)
    {
        $report = Report::findOrFail($id);
        $report->delete();

        return response()->json(['message' => 'Reporte eliminado correctamente.']);
    }
}
