<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */    public $withinTransaction = false;

    public function up(): void
    {
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id(); // Primary Key
            $table->string('name', 80)->nullable(); // Name
            $table->string('email', 120)->nullable(); // Email
            $table->string('subject', 120)->nullable(); // Subject
            $table->text('message'); // Message
            $table->boolean('read')->default(false); // Read
            $table->timestamps(); // Created At
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
    }
};