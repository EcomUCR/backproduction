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
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // Primary Key
            $table->string('username', 100)->unique(); // Email
            $table->string('email', 100)->unique(); // Email
            $table->string('password', 255); // Password
            $table->string('first_name', 80)->nullable(); // First Name
            $table->string('last_name', 80)->nullable(); // Last Name
            $table->text('image')->nullable(); // Image
            $table->boolean('status')->default(true); // Status
            $table->string('phone_number', 20)->nullable(); // Phone Number
            $table->string('role', 10); // Role
            $table->timestamps(); // Created At & Updated At
            $table->rememberToken();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};