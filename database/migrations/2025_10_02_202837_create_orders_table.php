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
        Schema::create('orders', function (Blueprint $table) {
            $table->id(); // Primary Key
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Foreign Key to Users
            $table->string('status', 20)->default('PENDING'); // Status
            $table->decimal('subtotal', 10, 2)->default(0); // Subtotal
            $table->decimal('shipping', 10, 2)->default(0); // Shipping
            $table->decimal('taxes', 10, 2)->default(0); // Taxes
            $table->decimal('total', 10, 2)->default(0); // Total
            $table->foreignId('address_id')->nullable()->constrained('addresses'); // Address
            $table->string('street', 150)->nullable(); // Street
            $table->string('city', 100)->nullable(); // City
            $table->string('state', 100)->nullable(); // State
            $table->string('zip_code', 20)->nullable(); // Zip Code
            $table->string('country', 100)->nullable(); // Country
            $table->string('payment_method', 30)->nullable(); // Payment Method
            $table->timestamps(); // Created At & Updated At
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};