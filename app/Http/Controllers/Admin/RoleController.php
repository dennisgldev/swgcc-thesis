<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->get();
        return response()->json($roles);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'permissions' => 'array',
            'permissions.*' => 'integer|exists:permissions,id'
        ]);
        $existingRole = Role::where('name', $validatedData['name'])
                            ->where('guard_name', 'web')
                            ->first();

        if ($existingRole) {
            return response()->json([
                'message' => 'Ya existe un rol con este nombre.',
            ], 400); // Código de estado 400 para indicar una solicitud incorrecta
        }

        // Establecer el guard_name por defecto a 'web'
        $role = Role::create([
            'name' => $validatedData['name'],
            'guard_name' => 'web'
        ]);

        if (isset($validatedData['permissions'])) {
            $role->syncPermissions($validatedData['permissions']);
        }

        return response()->json(['message' => 'Rol creado con éxito', 'role' => $role], 201);
    }

    public function update(Request $request, Role $role)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'permissions' => 'array',
            'permissions.*' => 'integer|exists:permissions,id'
        ]);

        // Actualizar el guard_name por defecto a 'web'
        $role->update([
            'name' => $validatedData['name'],
            'guard_name' => 'web'
        ]);

        $role->syncPermissions($validatedData['permissions']);

        return response()->json(['message' => 'Rol actualizado con éxito', 'role' => $role], 200);
    }


    public function destroy(Role $role)
    {
        Log::info('Eliminando rol:', ['role' => $role->toArray()]);

        $role->delete();
        return response()->json(null, 204);
    }

    public function permissions()
    {
        $permissions = Permission::all();
        return response()->json($permissions);
    }

}
