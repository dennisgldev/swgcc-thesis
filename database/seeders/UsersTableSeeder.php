<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::where('name', 'Administrador')->first();
        $docenteRole = Role::where('name', 'Docente')->first();
        $estudianteRole = Role::where('name', 'Estudiante')->first();
        $invitadoRole = Role::where('name', 'Invitado')->first();

        // Crear un usuario administrador específico
        User::create([
            'name' => 'admin',
            'cedula' => 'adminadmin',
            'email' => 'admin@ug.edu.ec',
            'password' => Hash::make('adminadmin'),
            'role_id' => $adminRole->id,
        ]);

        // Crear usuarios aleatorios
        $users = [
            [
                'name' => 'Dennis Orlando Gutierrez Leon',
                'cedula' => '0944080647',
                'email' => 'dennis.gutierrezl@ug.edu.ec',
                'password' => Hash::make('0944080647'),
                'role_id' => $estudianteRole->id,
            ],
            [
                'name' => 'John Doe',
                'cedula' => 'docenteeee',
                'email' => 'johndoe@example.com',
                'password' => Hash::make('password'),
                'role_id' => $docenteRole->id,
            ],
            [
                'name' => 'Jane Smith',
                'cedula' => 'estudiante',
                'email' => 'janesmith@example.com',
                'password' => Hash::make('password'),
                'role_id' => $estudianteRole->id,
            ],
            [
                'name' => 'Bob Brown',
                'cedula' => 'invitadooo',
                'email' => 'bobbrown@example.com',
                'password' => Hash::make('password'),
                'role_id' => $invitadoRole->id,
            ],
        ];

        foreach ($users as $userData) {
            User::create($userData);
        }
    }
}
