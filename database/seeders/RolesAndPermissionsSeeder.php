<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Crear los permisos
        $permissions = [
            'panel de gestión de usuarios',
            'panel de gestión de roles y permisos',
            'gestión de cursos y reportería',
            'ver cursos',
            'inscribirse a cursos',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Crear los roles y asignar permisos
        $roles = [
            'administrador' => [
                'panel de gestión de usuarios',
                'panel de gestión de roles y permisos',
                'gestión de cursos y reportería',
            ],
            'docente' => [
                'gestión de cursos y reportería',
            ],
            'estudiante' => [
                'ver cursos',
                'inscribirse a cursos',
            ],
            'invitado' => [
                'ver cursos',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($rolePermissions);
        }
    }
}
