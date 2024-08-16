<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function index()
    {
        return response()->json(User::with('role')->get());
    }

    public function store(Request $request)
    {
        // Validar los datos recibidos
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'cedula' => 'required|string|max:10|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role_id' => 'required|exists:roles,id', // Puede ser un rol base o personalizado
        ]);

        // Crear el nuevo usuario
        $user = User::create([
            'name' => $validatedData['name'],
            'cedula' => $validatedData['cedula'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
            'role_id' => $validatedData['role_id'], // Guardar el rol seleccionado
        ]);

        return response()->json(['message' => 'Usuario creado con éxito', 'user' => $user], 201);
    }

    public function update(Request $request, User $user)
    {
        Log::info('Datos recibidos para actualizar el usuario', $request->all());

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'cedula' => 'required|string|max:10|unique:users,cedula,' . $user->id,
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'sometimes|nullable|string|min:8',
            'role_id' => 'required|exists:roles,id',
        ]);

        Log::info('Datos validados para la actualización', $validatedData);

        if ($request->filled('password')) {
            $validatedData['password'] = Hash::make($validatedData['password']);
        } else {
            unset($validatedData['password']);
        }

        $user->update($validatedData);

        Log::info('Usuario actualizado con éxito', ['user_id' => $user->id]);

        return response()->json(['message' => 'Usuario actualizado con éxito', 'user' => $user], 200);
    }

    public function destroy(User $user)
    {
        $user->delete();

        return response()->json(null, 204);
    }

    public function edit(User $user)
    {
        return response()->json($user);
    }
}
