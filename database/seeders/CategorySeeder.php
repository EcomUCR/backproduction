<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Juegos',
            'Gaming',
            'Deportes',
            'Hogar',
            'Comida',
            'Ropa',
            'Herramientas',
            'Decoración',
            'Electrónica',
            'Mascotas',
            'Libros',
            'Belleza',
            'Salud',
            'Juguetes',
            'Automotriz',
            'Oficina',
            'Arte',
            'Música',
            'Jardinería',
            'Otros'
        ];

        foreach ($categories as $name) {
            Category::create(['name' => $name]);
        }
    }
}
