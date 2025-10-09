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
            $table->text('image'); // Image
            $table->text('link')->nullable(); // Link
            $table->string('type', 20)->default('MAIN'); // Type
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
        Schema::dropIfExists('banners');
    }
};