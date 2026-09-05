<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Marketplace Permissions
        $permissions = [
            // Admin permissions
            'manage users',
            'manage sellers',
            'manage customers',
            'manage categories',
            'manage products',
            'manage orders',
            'manage reviews',
            'manage banners',
            'manage settings',
            'view reports',
            'view activity logs',

            // Seller permissions
            'view seller dashboard',
            'manage own products',
            'view own orders',
            'manage own shop profile',
            'view own sales reports',

            // Customer permissions
            'browse products',
            'add to cart',
            'checkout orders',
            'view own orders',
            'cancel own order',
            'leave product reviews',
            'manage wishlist',
            'manage profile',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // 1. Admin Role
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->syncPermissions(Permission::all());

        // 2. Seller Role
        $sellerRole = Role::firstOrCreate(['name' => 'seller']);
        $sellerRole->syncPermissions([
            'view seller dashboard',
            'manage own products',
            'view own orders',
            'manage own shop profile',
            'view own sales reports',
        ]);

        // 3. Customer Role
        $customerRole = Role::firstOrCreate(['name' => 'customer']);
        $customerRole->syncPermissions([
            'browse products',
            'add to cart',
            'checkout orders',
            'view own orders',
            'cancel own order',
            'leave product reviews',
            'manage wishlist',
            'manage profile',
        ]);
    }
}
