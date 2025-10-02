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
        Schema::create('store_reviews', function (Blueprint $table) {
            $table->id(); // Primary Key
            $table->foreignId('store_id')->constrained('stores')->onDelete('cascade'); // Foreign Key to Stores
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Foreign Key to Users
            $table->integer('rating')->notNull(); // Rating
            $table->text('comment')->nullable(); // Comment
            $table->timestamps(); // Created At
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_reviews');
    }
};