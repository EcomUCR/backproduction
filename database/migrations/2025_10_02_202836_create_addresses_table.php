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
        Schema::create('addresses', function (Blueprint $table) {
            $table->id(); // Primary Key
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade'); // Foreign Key to Users
            $table->string('phone_number', 20)->notNull(); // Phone Number
            $table->string('street', 150)->nullable(); // Street
            $table->string('city', 100)->nullable(); // City
            $table->string('state', 100)->nullable(); // State
            $table->string('zip_code', 20)->nullable(); // Zip Code
            $table->string('country', 100)->nullable(); // Country
            $table->boolean('is_default')->default(false); // Is Default
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};