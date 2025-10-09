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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id(); // Primary Key
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade'); // Foreign Key to Orders
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade'); // Foreign Key to Products
            $table->foreignId('store_id')->constrained('stores')->onDelete('cascade'); // Foreign Key to Stores
            $table->integer('quantity'); // Quantity
            $table->decimal('unit_price', 10, 2); // Unit Price
            $table->integer('discount_pct')->default(0); // Discount Percentage
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};