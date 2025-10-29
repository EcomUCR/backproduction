<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('subtotal', 15, 2)->default(0)->change();
            $table->decimal('shipping', 15, 2)->default(0)->change();
            $table->decimal('taxes', 15, 2)->default(0)->change();
            $table->decimal('total', 15, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('subtotal', 10, 2)->default(0)->change();
            $table->decimal('shipping', 10, 2)->default(0)->change();
            $table->decimal('taxes', 10, 2)->default(0)->change();
            $table->decimal('total', 10, 2)->default(0)->change();
        });
    }
};
