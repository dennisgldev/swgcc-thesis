<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use App\Models\Permission;
use Illuminate\Support\Facades\Log;

class RoleController extends Controller
{
    public function index()
    {
        return response()->json(Role::all());
    }

    public function store(Request $request)
    {
        Log::info($request->all());
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:roles',
            'permission_role_id' => 'required|integer',
        ]);

        $role = Role::create(['name' => $validatedData['name']]);

        Permission::create([
            'name' => $this->getPermissionName($validatedData['permission_role_id']),
            'role_id' => $role->id,
        ]);

        return response()->json(['message' => 'Rol creado con éxito', 'role' => $role], 201);
    }

    public function update(Request $request, Role $role)
    {
        Log::info($request);
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'permission_role_id' => 'required|integer',
        ]);

        $role->update(['name' => $validatedData['name']]);

        $role->permissions()->delete();
        Permission::create([
            'name' => $this->getPermissionName($validatedData['permission_role_id']),
            'role_id' => $role->id,
        ]);

        return response()->json(['message' => 'Rol actualizado con éxito', 'role' => $role]);
    }

    private function getPermissionName($role_id)
    {
        switch ($role_id) {
            case 1:
                return 'Gestionar Usuarios';
            case 2:
                return 'Gestión de Cursos';
            case 3:
                return 'Realizar Cursos';
            case 4:
                return 'Externo';
            default:
                return 'Sin Permiso';
        }
    }
}
