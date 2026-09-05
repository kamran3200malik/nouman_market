<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('tag')->nullable(); // e.g. "HOT DEAL", "NEW ARRIVAL", "LIMITED TIME"
            $table->string('image');
            $table->string('link_url')->nullable();
            $table->string('button_text')->default('Shop Now');
            $table->decimal('price', 10, 2)->nullable();
            $table->string('position')->default('main_hero'); // main_hero, top_banner, sidebar_banner, bottom_promo
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('set null');
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
