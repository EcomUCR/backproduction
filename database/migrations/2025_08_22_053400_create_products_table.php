<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->text('description')->nullable();
            $table->decimal('discount', 5, 2)->default(0); // ✅ ahora decimal
            $table->integer('stock')->default(0);
            $table->decimal('price', 10, 2);
            $table->boolean('status')->default(true);
            $table->unsignedBigInteger('vendor_id');
            $table->timestamps(); // ✅ estándar

            $table->foreign('vendor_id')
                  ->references('id')->on('vendors')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void {
        Schema::dropIfExists('products');
    }
};
