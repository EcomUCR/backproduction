<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = json_decode(file_get_contents(database_path('seeders/data/products.json')), true);

        foreach ($products as $product) {
            DB::table('products')->insert([
                'id' => $product['id'],
                'store_id' => $product['store_id'],
                'sku' => $product['sku'],
                'name' => $product['name'],
                'image_1_url' => $product['image_1_url'] ?? null,
                'image_2_url' => $product['image_2_url'] ?? null,
                'image_3_url' => $product['image_3_url'] ?? null,
                'description' => $product['description'] ?? null,
                'price' => $product['price'],
                'discount_price' => $product['discount_price'] ?? null,
                'stock' => $product['stock'],
                'is_featured' => $product['is_featured'] ?? false,
                'created_at' => $product['created_at'] ?? now(),
                'updated_at' => $product['updated_at'] ?? now(),
                'status' => $product['status'] ?? 'ACTIVE',
                'details' => $product['details'] ?? null,
                'sold_count' => $product['sold_count'] ?? 0,
            ]);
        }
    }
}
