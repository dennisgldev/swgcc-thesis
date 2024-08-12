<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        Log::info('Iniciando el proceso de login'); // Log para verificar el inicio del proceso

        $credentials = $request->validate([
            'cedula' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        Log::info('Credenciales validadas', $credentials); // Log para verificar las credenciales

        if (Auth::attempt($credentials)) {
            Log::info('Autenticación exitosa'); // Log para verificar si la autenticación fue exitosa

            $request->session()->regenerate();

            $user = Auth::user();
            Log::info('Usuario autenticado', ['user_id' => $user->id, 'role' => $user->role->name]); // Log del usuario autenticado

            try {
                $token = $user->createToken('API Token')->plainTextToken;
                Log::info('Token creado con éxito'); // Log para verificar si el token fue creado
            } catch (\Exception $e) {
                Log::error('Error al crear el token', ['error' => $e->getMessage()]); // Log para capturar cualquier error en la creación del token
                return response()->json(['message' => 'Error al crear el token'], 500);
            }

            return response()->json([
                'message' => 'Inicio de sesión exitoso',
                'token' => $token,
                'role' => $user->role->name
            ], 200);
        }

        Log::warning('Falló la autenticación'); // Log para el caso de que la autenticación falle

        return response()->json(['message' => 'Las credenciales proporcionadas no coinciden con nuestros registros.'], 401);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Cierre de sesión exitoso'], 200);
    }

    public function user(Request $request)
    {
        return response()->json(Auth::user());
    }
}
