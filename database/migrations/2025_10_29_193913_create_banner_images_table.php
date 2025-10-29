<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * ⚡️ Desactivar transacciones automáticas para PostgreSQL (Neon)
     */
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::create('banner_images', function (Blueprint $table) {
            $table->id();
            $table->text('link'); // URL de la imagen
            $table->string('type', 20)->default('BACKGROUND'); // CHARACTER | BACKGROUND
            $table->string('alt_text', 150)->nullable(); // descripción para SEO/accesibilidad
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banner_images');
    }
};
