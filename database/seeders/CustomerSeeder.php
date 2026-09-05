<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            [
                'name' => 'Fatima Noor',
                'username' => 'fatimanoor',
                'email' => 'fatima@example.com',
                'phone' => '+923011122334',
                'city' => 'Lahore',
                'address' => 'DHA Phase 5, Sector C',
            ],
            [
                'name' => 'Zainab Malik',
                'username' => 'zainabm',
                'email' => 'zainab@example.com',
                'phone' => '+923224455667',
                'city' => 'Islamabad',
                'address' => 'E-11/2, Street 30',
            ],
            [
                'name' => 'Maryam Tariq',
                'username' => 'maryamt',
                'email' => 'maryam@example.com',
                'phone' => '+923347788990',
                'city' => 'Rawalpindi',
                'address' => 'Bahria Town Phase 4',
            ],
            [
                'name' => 'Hira Bilal',
                'username' => 'hirabilal',
                'email' => 'hira@example.com',
                'phone' => '+923459988776',
                'city' => 'Faisalabad',
                'address' => 'Kohinoor City Block B',
            ],
        ];

        foreach ($customers as $c) {
            $user = User::updateOrCreate(
                ['email' => $c['email']],
                [
                    'name' => $c['name'],
                    'username' => $c['username'],
                    'password' => Hash::make('password'),
                    'phone' => $c['phone'],
                    'role' => 'customer',
                    'is_active' => true,
                    'city' => $c['city'],
                    'address' => $c['address'],
                    'email_verified_at' => now(),
                ]
            );
            $user->assignRole('customer');
        }
    }
}
