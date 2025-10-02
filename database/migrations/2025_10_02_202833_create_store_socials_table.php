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
        Schema::create('store_socials', function (Blueprint $table) {
            $table->id(); // Primary Key
            $table->foreignId('store_id')->constrained('stores')->onDelete('cascade'); // Foreign Key to Stores
            $table->string('platform', 50); // Platform
            $table->text('url')->notNull(); // URL
            $table->timestamps(); // Created At & Updated At
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_socials');
    }
};