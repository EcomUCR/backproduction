<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */    public $withinTransaction = false;

    public function up(): void
    {
        Schema::create('discount_codes', function (Blueprint $table) {
            $table->id(); // Primary Key
            $table->string('code', 50)->unique(); // Code
            $table->text('description')->nullable(); // Description
            $table->string('scope', 20); // Scope
            $table->foreignId('admin_id')->nullable()->constrained('users'); // Admin ID
            $table->foreignId('store_id')->nullable()->constrained('stores'); // Store ID
            $table->foreignId('product_id')->nullable()->constrained('products'); // Product ID
            $table->integer('discount_pct'); // Discount Percentage
            $table->integer('max_uses'); // Max Uses
            $table->integer('used_count')->default(0); // Used Count
            $table->timestamp('valid_from')->nullable(); // Valid From
            $table->timestamp('valid_until')->nullable(); // Valid Until
            $table->boolean('is_active')->default(true); // Is Active
            $table->timestamps(); // Created At & Updated At
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discount_codes');
    }
};