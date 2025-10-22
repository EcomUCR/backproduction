<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    // 📜 Listar notificaciones del usuario autenticado
    public function index()
    {
        $user = Auth::user();

        $notifications = Notification::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json($notifications);
    }

    // 👁️ Marcar notificación como leída
    public function markAsRead($id)
    {
        $notification = Notification::findOrFail($id);

        $notification->update(['is_read' => true]);

        return response()->json(['message' => 'Notificación marcada como leída']);
    }

    // 🗄️ Archivar notificación
    public function archive($id)
    {
        $notification = Notification::findOrFail($id);

        $notification->update(['is_archived' => true]);

        return response()->json(['message' => 'Notificación archivada']);
    }

    // 🧹 Eliminar notificación
    public function destroy($id)
    {
        $notification = Notification::findOrFail($id);
        $notification->delete();

        return response()->json(['message' => 'Notificación eliminada']);
    }

    // 🆕 Crear una nueva notificación (manual o desde eventos del sistema)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'nullable|in:CUSTOMER,SELLER,ADMIN',
            'type' => 'required|string|max:80',
            'title' => 'required|string|max:120',
            'message' => 'required|string',
            'related_id' => 'nullable|integer',
            'related_type' => 'nullable|string|max:80',
            'priority' => 'nullable|in:LOW,NORMAL,HIGH',
            'data' => 'nullable',
        ]);
        if (is_string($validated['data'])) {
            $validated['data'] = json_decode($validated['data'], true);
        }

        $notification = Notification::create($validated);

        return response()->json($notification, 201);
    }
}
