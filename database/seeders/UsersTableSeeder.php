<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/users.json');
        $json = file_get_contents($path);
        $users = json_decode($json, true);

        // ⚙️ Desactivar restricciones de foreign keys
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        DB::table('users')->truncate();

        // ✅ Volver a activar
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        DB::table('users')->insert($users);
    }
}
