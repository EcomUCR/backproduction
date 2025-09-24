<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
       /* // 🔹 Crear usuario de prueba (admin)
        User::create([
            'email'    => 'admin@example.com',
            'password' => Hash::make('password123'), // cambia la clave según necesites
        ]);*/

        // 🔹 Llamar a otros seeders
        $this->call([
            CategorySeeder::class,
        ]);
    }
}
