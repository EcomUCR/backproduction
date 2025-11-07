<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            StoreCategorySeeder::class,
            CategorySeeder::class,
            UsersTableSeeder::class,
            StoresTableSeeder::class,
            ProductSeeder::class,
            ProductCategoryTableSeeder::class,
        ]);
    }
}
