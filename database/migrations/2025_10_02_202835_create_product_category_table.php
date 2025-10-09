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
        Schema::create('product_category', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade'); // Foreign Key to Products
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade'); // Foreign Key to Categories
            $table->primary(['product_id', 'category_id']); // Composite Primary Key
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_category');
    }
};