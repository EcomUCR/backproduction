<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StoreCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['id' => 1, 'name' => 'Bicicletería'],
            ['id' => 2, 'name' => 'Emprendimiento'],
            ['id' => 3, 'name' => 'Farmacia'],
            ['id' => 4, 'name' => 'Ferretería'],
            ['id' => 5, 'name' => 'Floristería'],
            ['id' => 6, 'name' => 'Joyería'],
            ['id' => 7, 'name' => 'Librería y Papelería'],
            ['id' => 8, 'name' => 'Macrobiótica'],
            ['id' => 9, 'name' => 'Panadería / Pastelería'],
            ['id' => 10, 'name' => 'Supermercado'],
            ['id' => 11, 'name' => 'Tienda de Belleza / Cosméticos'],
            ['id' => 12, 'name' => 'Tienda de Deportes'],
            ['id' => 13, 'name' => 'Tienda de Hogar / Decoración'],
            ['id' => 14, 'name' => 'Tienda de Juguetes'],
            ['id' => 15, 'name' => 'Tienda de Mascotas'],
            ['id' => 16, 'name' => 'Tienda de Regalos'],
            ['id' => 17, 'name' => 'Tienda de Ropa'],
            ['id' => 18, 'name' => 'Tienda de Suplementos / Nutrición'],
            ['id' => 19, 'name' => 'Tienda de Tecnología'],
            ['id' => 20, 'name' => 'Zapatería'],
        ];

        DB::table('store_categories')->insert($categories);
    }
}
