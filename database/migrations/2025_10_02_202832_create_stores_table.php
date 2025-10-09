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
        Schema::create('stores', function (Blueprint $table) {
            $table->id(); // Primary Key
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Foreign Key to Users
            $table->string('name', 80)->notNull(); // Name
            $table->string('slug', 100)->unique()->notNull(); // Slug
            $table->text('description')->nullable(); // Description
            $table->text('image')->nullable(); // Image
            $table->text('banner')->nullable(); // Banner
            $table->foreignId('category_id')->nullable()->constrained('store_categories'); // Store Category
            $table->string('business_name', 150)->nullable(); // Business Name
            $table->string('tax_id', 50)->nullable(); // Tax ID
            $table->string('legal_type', 30)->nullable(); // Legal Type
            $table->text('registered_address')->nullable(); // Registered Address
            $table->text('address')->nullable(); // Registered Address
            $table->string('support_email', 120)->nullable(); // Support Email
            $table->string('support_phone', 30)->nullable(); // Support Phone
            $table->boolean('is_verified')->default(false); // Is Verified
            $table->timestamp('verification_date')->nullable(); // Verification Date
            $table->string('status', 20)->default('ACTIVE'); // Status
            $table->timestamps(); // Created At & Updated At
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
