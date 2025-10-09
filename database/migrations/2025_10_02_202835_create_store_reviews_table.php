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
        Schema::create('store_reviews', callback: function (Blueprint $table) {
            $table->id(); // Primary Key
            $table->foreignId('store_id')->constrained('stores')->onDelete('cascade'); // Foreign Key to Stores
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Foreign Key to Users
            $table->integer('rating'); // Rating
            $table->text('comment')->nullable(); // Comment
            $table->boolean('like')->nullable(); // Comment
            $table->boolean('dislike')->nullable(); // Comment
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