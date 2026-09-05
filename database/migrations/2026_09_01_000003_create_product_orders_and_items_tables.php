<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // Customer
            $table->foreignId('seller_id')->nullable()->constrained('users')->onDelete('set null'); // Seller (if single seller order)
            
            // Customer Info
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone');
            
            // Delivery Info
            $table->text('shipping_address');
            $table->string('city');
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->default('Pakistan');
            $table->text('notes')->nullable();
            
            // Financial Breakdown
            $table->decimal('subtotal', 10, 2)->default(0.00);
            $table->decimal('discount_amount', 10, 2)->default(0.00);
            $table->decimal('shipping_fee', 10, 2)->default(0.00);
            $table->decimal('tax_amount', 10, 2)->default(0.00);
            $table->decimal('total_amount', 10, 2);
            
            // Statuses
            $table->string('payment_method')->default('cash_on_delivery'); // cash_on_delivery, card, jazzcash, easypaisa, bank_transfer
            $table->string('payment_status')->default('unpaid'); // unpaid, paid, refunded, failed
            $table->string('order_status')->default('pending'); // pending, processing, shipped, delivered, cancelled
            
            // Shipping tracking
            $table->string('tracking_number')->nullable();
            $table->string('courier_name')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_order_id')->constrained('product_orders')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('set null');
            $table->foreignId('seller_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('product_name');
            $table->string('product_image')->nullable();
            $table->decimal('unit_price', 10, 2);
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('total_price', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_order_items');
        Schema::dropIfExists('product_orders');
    }
};
