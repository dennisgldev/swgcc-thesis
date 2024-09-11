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
                'name' => 'John Francisco Montenegro Martillo',
                'cedula' => '0909876591',
                'email' => 'johndoe@example.com',
                'password' => Hash::make('password'),
                'role' => 'docente',
            ],
            [
                'name' => 'Noelia Maria Quinteros Quimis',
                'cedula' => '2409876543',
                'email' => 'janesmith@example.com',
                'password' => Hash::make('password'),
                'role' => 'estudiante',
            ],
            [
                'name' => 'Javier Noelio Gonzalez Alvarez',
                'cedula' => '0907522577', //Valida
                'email' => 'bobbrown@example.com',
                'password' => Hash::make('password'),
                'role' => 'invitado',
            ],
            [
                'name' => 'Alicia Daniela Gines Villao',
                'cedula' => '1711422319', // Ejemplo de cédula válida
                'email' => 'alicejohnson@example.com',
                'password' => Hash::make('password'),
                'role' => 'docente',
            ],
            [
                'name' => 'Carlos Alberto Jaume Herrera',
                'cedula' => '1709876542', // Ejemplo de cédula no válida
                'email' => 'carlosherrera@example.com',
                'password' => Hash::make('password'),
                'role' => 'estudiante',
            ],
            [
                'name' => 'Maria Cristina Lopez Gonzalez',
                'cedula' => '0912345675', // Ejemplo de cédula no válida
                'email' => 'mariagonzalez@example.com',
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
