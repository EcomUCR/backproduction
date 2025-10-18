<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public $withinTransaction = false;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();

            $table->string('code', 50)->unique();

            $table->text('description')->nullable();

            $table->enum('type', ['PERCENTAGE', 'FIXED', 'FREE_SHIPPING']);

            $table->decimal('value', 10, 2);

            $table->decimal('min_purchase', 10, 2)->nullable();

            $table->decimal('max_discount', 10, 2)->nullable();

            $table->foreignId('store_id')->nullable()->constrained('stores')->onDelete('set null');
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');

            $table->integer('usage_limit')->default(1);
            $table->integer('usage_per_user')->default(1);

            $table->timestamp('expires_at')->nullable();

            $table->boolean('active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
