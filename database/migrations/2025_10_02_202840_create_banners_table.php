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
        Schema::create('banners', function (Blueprint $table) {
            $table->id(); // Primary Key
            $table->string('title', 100)->nullable(); // Title
            $table->text('subtitle')->nullable(); // Subtitle
            $table->text('character')->nullable(); // Optional character image or icon
            $table->text('image'); // Main Image
            $table->text('link')->nullable(); // Link (URL or route)
            $table->string('btn_text', 50)->nullable(); // Button Text
            $table->enum('btn_color', ['MORADO', 'AMARILLO', 'NARANJA', 'GRADIENTE'])->nullable(); // Button color style
            $table->enum('type', ['LARGE', 'SHORT', 'SLIDER'])->default('SLIDER'); // Banner type
            $table->enum('orientation', ['LEFT', 'RIGTH'])->nullable(); // Orientation (LEFT/RIGHT)
            $table->integer('position')->nullable(); // Display order position
            $table->boolean('is_active')->default(true); // Active state
            $table->timestamps(); // Created At & Updated At
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
