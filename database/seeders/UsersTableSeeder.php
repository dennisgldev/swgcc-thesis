<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear un usuario administrador específico
        $adminUser = User::create([
            'name' => 'admin',
            'cedula' => 'adminadmin',
            'email' => 'admin@ug.edu.ec',
            'password' => Hash::make('adminadmin'),
        ]);
        $adminUser->assignRole('administrador');

        // Crear usuarios aleatorios
        $users = [
            [
                'name' => 'Dennis Orlando Gutierrez Leon',
                'cedula' => '0944080647',
                'email' => 'dennis.gutierrezl@ug.edu.ec',
                'password' => Hash::make('0944080647'),
                'role' => 'estudiante',
            ],
            [
                'name' => 'John Doe',
                'cedula' => 'docenteeee',
                'email' => 'johndoe@example.com',
                'password' => Hash::make('password'),
                'role' => 'docente',
            ],
            [
                'name' => 'Jane Smith',
                'cedula' => 'estudiante',
                'email' => 'janesmith@example.com',
                'password' => Hash::make('password'),
                'role' => 'estudiante',
            ],
            [
                'name' => 'Bob Brown',
                'cedula' => 'invitadooo',
                'email' => 'bobbrown@example.com',
                'password' => Hash::make('password'),
                'role' => 'invitado',
            ],
        ];

        foreach ($users as $userData) {
            $user = User::create([
                'name' => $userData['name'],
                'cedula' => $userData['cedula'],
                'email' => $userData['email'],
                'password' => $userData['password'],
            ]);
            $user->assignRole($userData['role']);
        }
    }
}
