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
                'cedula' => '1234567890',
                'email' => 'johndoe@example.com',
                'password' => Hash::make('password'),
                'role' => 'docente',
            ],
            [
                'name' => 'Jane Smith',
                'cedula' => '0987654321',
                'email' => 'janesmith@example.com',
                'password' => Hash::make('password'),
                'role' => 'estudiante',
            ],
            [
                'name' => 'Bob Brown',
                'cedula' => '1029384756',
                'email' => 'bobbrown@example.com',
                'password' => Hash::make('password'),
                'role' => 'invitado',
            ],
            [
                'name' => 'Alice Johnson',
                'cedula' => '1098765432',
                'email' => 'alicejohnson@example.com',
                'password' => Hash::make('password'),
                'role' => 'docente',
            ],
            [
                'name' => 'Carlos Herrera',
                'cedula' => '2049583726',
                'email' => 'carlosherrera@example.com',
                'password' => Hash::make('password'),
                'role' => 'estudiante',
            ],
            [
                'name' => 'Maria Gonzalez',
                'cedula' => '3948571620',
                'email' => 'mariagonzalez@example.com',
                'password' => Hash::make('password'),
                'role' => 'invitado',
            ],
            [
                'name' => 'Luis Ramirez',
                'cedula' => '2847395610',
                'email' => 'luisramirez@example.com',
                'password' => Hash::make('password'),
                'role' => 'estudiante',
            ],
            [
                'name' => 'Ana Martinez',
                'cedula' => '4567890123',
                'email' => 'anamartinez@example.com',
                'password' => Hash::make('password'),
                'role' => 'docente',
            ],
            [
                'name' => 'Pedro Suarez',
                'cedula' => '3344556677',
                'email' => 'pedrosuarez@example.com',
                'password' => Hash::make('password'),
                'role' => 'invitado',
            ],
            [
                'name' => 'Sofia Vargas',
                'cedula' => '6655443321',
                'email' => 'sofiavargas@example.com',
                'password' => Hash::make('password'),
                'role' => 'estudiante',
            ],
            [
                'name' => 'Fernando Torres',
                'cedula' => '1112223334',
                'email' => 'fernandotorres@example.com',
                'password' => Hash::make('password'),
                'role' => 'docente',
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
