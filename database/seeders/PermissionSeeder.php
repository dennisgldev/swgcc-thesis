<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            ['role_id' => 1, 'permission_name' => 'Gestionar usuarios'],  // Rol 1 asociado al permiso 'Gestionar usuarios'
            ['role_id' => 2, 'permission_name' => 'Gestionar cursos'],   // Rol 2 asociado al permiso 'Gestionar cursos'
            ['role_id' => 3, 'permission_name' => 'Realizar cursos'],    // Rol 3 asociado al permiso 'Realizar cursos'
            ['role_id' => 4, 'permission_name' => 'Externo'],            // Rol 4 asociado al permiso 'Externo'
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }
    }
}
