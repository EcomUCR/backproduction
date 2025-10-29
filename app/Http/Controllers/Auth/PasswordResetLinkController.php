<?php

namespace App\Http\Controllers\Auth;
use App\Models\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        try {
            // 🔹 Verificar si el correo existe en la base de datos
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                // Si no existe, devolvemos un error 404 controlado
                return response()->json([
                    'message' => 'El correo no está registrado en el sistema.'
                ], 404);
            }

            \Log::info('Entrando a Password::sendResetLink');
            $status = Password::sendResetLink($request->only('email'));
            \Log::info('Resultado de Password::sendResetLink', [$status]);

            // 🔹 Evaluar resultado del envío
            if ($status === Password::RESET_LINK_SENT) {
                return response()->json([
                    'message' => 'Se ha enviado el enlace de recuperación a tu correo.'
                ], 200);
            }

            // 🔹 Si el envío falla (por ejemplo, throttling)
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error en Password Reset: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error interno: ' . $e->getMessage(),
            ], 500);
        }
    }

}
