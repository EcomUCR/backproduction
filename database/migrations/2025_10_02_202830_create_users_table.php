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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100)->unique();
            $table->string('email', 100)->unique();
            $table->string('password', 255);
            $table->string('first_name', 80)->nullable();
            $table->string('last_name', 80)->nullable();
            $table->text('image')->nullable();
            $table->boolean('status')->default(true);
            $table->string('phone_number', 20)->nullable();
            $table->string('role', 10);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
