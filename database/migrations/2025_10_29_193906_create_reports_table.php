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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')
                ->nullable()
                ->constrained('orders')
                ->onDelete('set null'); // si se borra el pedido, mantener el reclamo
            $table->string('name', 100);
            $table->string('email', 120);
            $table->string('subject', 120)->nullable();
            $table->text('description');
            $table->string('status', 20)->default('PENDING'); // PENDING | IN_REVIEW | RESOLVED | REJECTED
            $table->text('admin_notes')->nullable();
            $table->boolean('read')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
