<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            AdminUserSeeder::class,
            CustomerSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
            BannerSeeder::class,
            ReviewSeeder::class,
            OrderSeeder::class,
            SettingSeeder::class,
            BlogSeeder::class,
        ]);
    }
}
