<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductOrder;
use App\Models\ProductOrderItem;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $customer = User::where('email', 'customer@example.com')->first();
        $fatima = User::where('email', 'fatima@example.com')->first();
        $ordinary = Product::where('brand', 'The Ordinary')->first();
        $cerave = Product::where('brand', 'CeraVe')->first();
        $fenty = Product::where('brand', 'Fenty Beauty')->first();
        $olaplex = Product::where('brand', 'Olaplex')->first();

        if (!$customer || !$ordinary || !$cerave) {
            return;
        }

        // 1. Delivered Order for Customer Sara
        $order1 = ProductOrder::updateOrCreate(
            ['order_number' => 'ORD-2026-08121'],
            [
                'user_id' => $customer->id,
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'customer_phone' => $customer->phone,
                'shipping_address' => 'House 42, Street 9, DHA Phase 6',
                'city' => 'Karachi',
                'state' => 'Sindh',
                'postal_code' => '75500',
                'subtotal' => 7050.00,
                'discount_amount' => 500.00,
                'shipping_fee' => 250.00,
                'tax_amount' => 0.00,
                'total_amount' => 6800.00,
                'payment_method' => 'cash_on_delivery',
                'payment_status' => 'paid',
                'order_status' => 'delivered',
                'tracking_number' => 'TCS-992817263',
                'courier_name' => 'TCS Express',
                'created_at' => now()->subDays(5),
            ]
        );

        ProductOrderItem::firstOrCreate(
            ['product_order_id' => $order1->id, 'product_id' => $ordinary->id],
            [
                'seller_id' => $ordinary->user_id,
                'product_name' => $ordinary->name,
                'product_image' => $ordinary->image,
                'unit_price' => $ordinary->price,
                'quantity' => 1,
                'total_price' => $ordinary->price,
            ]
        );

        ProductOrderItem::firstOrCreate(
            ['product_order_id' => $order1->id, 'product_id' => $cerave->id],
            [
                'seller_id' => $cerave->user_id,
                'product_name' => $cerave->name,
                'product_image' => $cerave->image,
                'unit_price' => $cerave->price,
                'quantity' => 1,
                'total_price' => $cerave->price,
            ]
        );

        // 2. Shipped / In Transit Order
        if ($fenty) {
            $order2 = ProductOrder::updateOrCreate(
                ['order_number' => 'ORD-2026-09042'],
                [
                    'user_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'customer_email' => $customer->email,
                    'customer_phone' => $customer->phone,
                    'shipping_address' => 'House 42, Street 9, DHA Phase 6',
                    'city' => 'Karachi',
                    'state' => 'Sindh',
                    'postal_code' => '75500',
                    'subtotal' => 7400.00,
                    'discount_amount' => 0.00,
                    'shipping_fee' => 0.00, // Free shipping
                    'tax_amount' => 0.00,
                    'total_amount' => 7400.00,
                    'payment_method' => 'card',
                    'payment_status' => 'paid',
                    'order_status' => 'shipped',
                    'tracking_number' => 'LEO-441829019',
                    'courier_name' => 'Leopard Courier',
                    'created_at' => now()->subDays(1),
                ]
            );

            ProductOrderItem::firstOrCreate(
                ['product_order_id' => $order2->id, 'product_id' => $fenty->id],
                [
                    'seller_id' => $fenty->user_id,
                    'product_name' => $fenty->name,
                    'product_image' => $fenty->image,
                    'unit_price' => $fenty->price,
                    'quantity' => 1,
                    'total_price' => $fenty->price,
                ]
            );
        }

        // 3. Pending Order for customer Fatima
        if ($fatima && $olaplex) {
            $order3 = ProductOrder::updateOrCreate(
                ['order_number' => 'ORD-2026-09053'],
                [
                    'user_id' => $fatima->id,
                    'customer_name' => $fatima->name,
                    'customer_email' => $fatima->email,
                    'customer_phone' => $fatima->phone,
                    'shipping_address' => 'DHA Phase 5, Sector C',
                    'city' => 'Lahore',
                    'state' => 'Punjab',
                    'postal_code' => '54000',
                    'subtotal' => 8800.00,
                    'discount_amount' => 0.00,
                    'shipping_fee' => 250.00,
                    'tax_amount' => 0.00,
                    'total_amount' => 9050.00,
                    'payment_method' => 'cash_on_delivery',
                    'payment_status' => 'unpaid',
                    'order_status' => 'pending',
                    'created_at' => now(),
                ]
            );

            ProductOrderItem::firstOrCreate(
                ['product_order_id' => $order3->id, 'product_id' => $olaplex->id],
                [
                    'seller_id' => $olaplex->user_id,
                    'product_name' => $olaplex->name,
                    'product_image' => $olaplex->image,
                    'unit_price' => $olaplex->price,
                    'quantity' => 1,
                    'total_price' => $olaplex->price,
                ]
            );
        }
    }
}
