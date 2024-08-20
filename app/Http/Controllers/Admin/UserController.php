<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        // Devuelve los usuarios con sus roles y permisos
        return response()->json(User::with('roles', 'permissions')->get());
    }

    public function store(Request $request)
    {
        Log::info('Datos recibidos para crear un usuario', $request->all());

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'cedula' => 'required|string|max:10|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role_id' => 'required|integer|exists:roles,id', // Cambiado a role_id y asegurado que sea un ID válido
        ]);

        Log::info('Datos validados para la creación de usuario', $validatedData);

        $user = User::create([
            'name' => $validatedData['name'],
            'cedula' => $validatedData['cedula'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
        ]);

        // Asignar el rol por ID
        $role = Role::find($validatedData['role_id']);
        if ($role) {
            Log::info('Asignando rol al usuario', ['role_id' => $role->id, 'role_name' => $role->name]);
            $user->assignRole($role->name);
        } else {
            Log::warning('El rol no se encontró para el ID dado', ['role_id' => $validatedData['role_id']]);
        }

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
            'role_id' => 'required|integer|exists:roles,id', // Se recibe un solo role_id
        ]);

        Log::info('Datos validados para la actualización de usuario', $validatedData);

        // Actualizar el usuario
        if ($request->filled('password')) {
            $validatedData['password'] = Hash::make($validatedData['password']);
        } else {
            unset($validatedData['password']);
        }

        $user->update($validatedData);

        // Sincronizar rol
        $role = Role::find($validatedData['role_id']);
        if ($role) {
            Log::info('Sincronizando rol al usuario', ['role_id' => $role->id, 'role_name' => $role->name]);
            $user->syncRoles([$role->name]); // Sincronizar asegura que solo un rol esté asignado
        } else {
            Log::warning('El rol no se encontró para el ID dado', ['role_id' => $validatedData['role_id']]);
        }

        Log::info('Usuario actualizado con éxito', ['user_id' => $user->id]);

        return response()->json(['message' => 'Usuario actualizado con éxito', 'user' => $user], 200);
    }

    public function destroy(User $user)
    {
        // Eliminar lógicamente el usuario
        $user->delete();
    
        // Retornar una respuesta vacía con el código de estado 204 (No Content)
        return response()->json(null, 204);
    }    

    public function edit(User $user)
    {
        // Devuelve el usuario con sus roles y permisos
        return response()->json($user->load('roles', 'permissions'));
    }
}
