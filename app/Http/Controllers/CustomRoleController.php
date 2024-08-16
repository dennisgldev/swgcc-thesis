<?php

namespace App\Http\Controllers;

use App\Models\CustomRole;
use Illuminate\Http\Request;

class CustomRoleController extends Controller
{
    public function index()
    {
        $customRoles = CustomRole::with('role')->get();
        return response()->json($customRoles);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'role_id' => 'required|integer|exists:roles,id',
        ]);

        try {
            CustomRole::create($validated);
            return response()->json(['message' => 'Rol personalizado creado correctamente.'], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al crear el rol personalizado.'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'role_id' => 'required|integer|exists:roles,id',
        ]);

        try {
            $customRole = CustomRole::findOrFail($id);
            $customRole->update($validated);
            return response()->json(['message' => 'Rol personalizado actualizado correctamente.'], 200);
        } catch (\Exception $e) {
            Log::error('Error al actualizar el rol personalizado: ' . $e->getMessage());
            return response()->json(['error' => 'Error al actualizar el rol personalizado.'], 500);
        }
    }


    public function destroy($id)
    {
        $customRole = CustomRole::findOrFail($id);
        $customRole->delete();

        return response()->json(['message' => 'Rol personalizado eliminado correctamente.'], 200);
    }
}
