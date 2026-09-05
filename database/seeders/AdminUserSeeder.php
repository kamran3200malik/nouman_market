<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Super Admin
        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Marketplace Admin',
                'username' => 'admin',
                'password' => Hash::make('password'),
                'phone' => '+923001234567',
                'role' => 'admin',
                'is_active' => true,
                'city' => 'Karachi',
                'address' => 'Clifton Block 5',
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole('admin');

        // 2. Verified Sellers
        $seller1 = User::updateOrCreate(
            ['email' => 'seller@example.com'],
            [
                'name' => 'Ayla Khan',
                'username' => 'aylacosmetics',
                'password' => Hash::make('password'),
                'phone' => '+923219876543',
                'role' => 'seller',
                'is_active' => true,
                'shop_name' => 'Ayla Luxe Cosmetics',
                'shop_slug' => 'ayla-luxe-cosmetics',
                'shop_description' => 'Official distributor of 100% genuine luxury skincare, Korean beauty serums, and organic hair treatments.',
                'seller_status' => 'approved',
                'city' => 'Lahore',
                'address' => 'Gulberg III, MM Alam Road',
                'email_verified_at' => now(),
            ]
        );
        $seller1->assignRole('seller');

        $seller2 = User::updateOrCreate(
            ['email' => 'glow@example.com'],
            [
                'name' => 'Glow Essence Store',
                'username' => 'glowessence',
                'password' => Hash::make('password'),
                'phone' => '+923335554444',
                'role' => 'seller',
                'is_active' => true,
                'shop_name' => 'Glow Essence Pakistan',
                'shop_slug' => 'glow-essence-pk',
                'shop_description' => 'Dermatologist-recommended clinical skincare and cruelty-free wellness cosmetics.',
                'seller_status' => 'approved',
                'city' => 'Islamabad',
                'address' => 'F-7 Markaz',
                'email_verified_at' => now(),
            ]
        );
        $seller2->assignRole('seller');

        // 3. Demo Customer
        $customer = User::updateOrCreate(
            ['email' => 'customer@example.com'],
            [
                'name' => 'Sara Ahmed',
                'username' => 'saraahmed',
                'password' => Hash::make('password'),
                'phone' => '+923123456789',
                'role' => 'customer',
                'is_active' => true,
                'city' => 'Karachi',
                'address' => 'Defence Phase 6, Street 14',
                'email_verified_at' => now(),
            ]
        );
        $customer->assignRole('customer');
    }
}
