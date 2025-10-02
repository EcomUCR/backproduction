<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id(); // Primary Key
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Foreign Key to Users
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('cascade'); // Foreign Key to Orders
            $table->string('type', 10); // Type
            $table->decimal('amount', 12, 2)->notNull(); // Amount
            $table->string('currency', 10)->default('CRC'); // Currency
            $table->text('description')->nullable(); // Description
            $table->timestamps(); // Created At
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};