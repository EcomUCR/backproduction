<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('role', ['CUSTOMER', 'SELLER', 'ADMIN'])->nullable();
            $table->string('type', 80); // Ej: ORDER_PLACED, STOCK_OUT, STORE_PENDING_VERIFICATION
            $table->string('title', 120);
            $table->text('message');
            $table->unsignedBigInteger('related_id')->nullable();
            $table->string('related_type', 80)->nullable();
            $table->boolean('is_read')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->enum('priority', ['LOW', 'NORMAL', 'HIGH'])->default('NORMAL');
            $table->json('data')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
