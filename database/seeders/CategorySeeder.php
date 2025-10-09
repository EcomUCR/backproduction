<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['id' => 1, 'name' => 'Arte'],
            ['id' => 2, 'name' => 'Automotriz'],
            ['id' => 3, 'name' => 'Belleza'],
            ['id' => 4, 'name' => 'Comida'],
            ['id' => 5, 'name' => 'Decoración'],
            ['id' => 6, 'name' => 'Deportes'],
            ['id' => 7, 'name' => 'Gaming'],
            ['id' => 8, 'name' => 'Herramientas'],
            ['id' => 9, 'name' => 'Hogar'],
            ['id' => 10, 'name' => 'Jardinería'],
            ['id' => 11, 'name' => 'Juegos'],
            ['id' => 12, 'name' => 'Juguetes'],
            ['id' => 13, 'name' => 'Libros'],
            ['id' => 14, 'name' => 'Limpieza'],
            ['id' => 15, 'name' => 'Mascotas'],
            ['id' => 16, 'name' => 'Música'],
            ['id' => 17, 'name' => 'Oficina'],
            ['id' => 18, 'name' => 'Ropa'],
            ['id' => 19, 'name' => 'Salud'],
            ['id' => 20, 'name' => 'Tecnología'],
            ['id' => 21, 'name' => 'Otros'],
        ];

        DB::table('categories')->insert($categories);
    }
}
