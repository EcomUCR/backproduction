<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id(); // Primary Key
            $table->foreignId('store_id')->constrained('stores')->onDelete('cascade'); // Foreign Key to Stores
            $table->string('sku', 30)->unique(); // SKU
            $table->string('name', 80); // Name
            $table->text('image_1_url'); // Image 1
            $table->text('image_2_url')->nullable(); // Image 2
            $table->text('image_3_url')->nullable(); // Image 3
            $table->text('description')->nullable(); // Description
            $table->text('details')->nullable(); // Details
            $table->decimal('price', 10, 2); // Price
            $table->decimal('discount_price', 10, 2)->default(0); // Discount Price
            $table->integer('stock')->default(0); // Stock
            $table->unsignedBigInteger('sold_count')->default(0); // 🔹 Veces vendido
            $table->string('status')->default("DRAFT"); // Status
            $table->boolean('is_featured')->default(false); // Is Featured
            $table->timestamps(); // Created At & Updated At
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
