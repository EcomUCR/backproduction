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
        Schema::create('store_banners', function (Blueprint $table) {
            $table->id(); // Primary Key
            $table->foreignId('store_id')->constrained('stores')->onDelete('cascade'); // Foreign Key to Stores
            $table->string('type', 20)->default('SMALL'); // Type
            $table->text('image')->notNull(); // Image
            $table->text('link')->nullable(); // Link
            $table->integer('position')->nullable(); // Position
            $table->boolean('is_active')->default(true); // Is Active
            $table->timestamps(); // Created At & Updated At
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_banners');
    }
};