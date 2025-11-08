<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StoreCategorySeeder extends Seeder
{
     public function run(): void
    {
        $path = database_path('seeders/data/store_categories.json');
        $json = file_get_contents($path);
        $data = json_decode($json, true);

        // ⚙️ Desactivar temporalmente las claves foráneas
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // 🧹 Limpiar la tabla antes de insertar
        DB::table('store_categories')->truncate();

        // ✅ Reactivar las claves foráneas
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 🚀 Insertar los registros desde el JSON
        DB::table('store_categories')->insert($data);
    }
}
