<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;

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
     * Crear un nuevo reporte
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'nullable|exists:orders,id',
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:120',
            'subject' => 'nullable|string|max:120',
            'description' => 'required|string',
        ]);

        $report = Report::create($validated);

        return response()->json([
            'message' => 'Reporte creado correctamente.',
            'report' => $report
        ], 201);
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
