<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Ejecutar las migraciones.
     */
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::create('page_banners', function (Blueprint $table) {
            $table->id(); // Primary Key
            $table->string('page_name', 50); // Ejemplo: 'home', 'cart', 'product'
            $table->tinyInteger('slot_number'); // 1 o 2
            $table->foreignId('banner_id')->constrained('banners')->onDelete('cascade'); // Relación con banners
            $table->timestamps(); // created_at & updated_at
        });
    }

    /**
     * Revertir las migraciones.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_banners');
    }
};
