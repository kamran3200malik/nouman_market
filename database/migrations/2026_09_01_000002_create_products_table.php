<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // Seller / Vendor (null = admin / marketplace)
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->nullable()->unique();
            $table->string('category')->nullable(); // Denormalized category name for backward compatibility
            $table->string('brand')->nullable();
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->string('image')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('original_price', 10, 2)->nullable();
            $table->decimal('cost_price', 10, 2)->nullable();
            $table->decimal('rating', 3, 2)->default(5.00);
            $table->unsignedInteger('reviews_count')->default(0);
            $table->unsignedInteger('stock_quantity')->default(50);
            $table->boolean('in_stock')->default(true);
            $table->string('badge')->nullable(); // e.g. "Bestseller", "New", "20% OFF", "Hot"
            $table->json('short_features')->nullable();
            $table->text('usage_instructions')->nullable();
            $table->text('ingredients')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_trending')->default(false);
            $table->boolean('is_active')->default(true);
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('approved');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('image_path');
            $table->boolean('is_primary')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('products');
    }
};
