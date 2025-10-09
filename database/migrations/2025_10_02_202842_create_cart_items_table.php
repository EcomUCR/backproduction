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
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id(); // Primary Key
            $table->foreignId('cart_id')->constrained('carts')->onDelete('cascade'); // Foreign Key to Carts
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade'); // Foreign Key to Products
            $table->integer('quantity')->default(1); // Quantity
            $table->decimal('unit_price', 10, 2); // Unit Price
            $table->timestamps(); // Created At & Updated At
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};