<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StoresTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = database_path('seeders/data/stores.json');
        $json = file_get_contents($path);
        $stores = json_decode($json, true);

        // ⚙️ Desactivar las restricciones de claves foráneas temporalmente
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // 🧹 Limpiar la tabla antes de insertar
        DB::table('stores')->truncate();

        // ✅ Reactivar las restricciones
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 🚀 Insertar los datos del JSON
        DB::table('stores')->insert($stores);
    }
}
