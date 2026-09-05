<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General & Branding
            'site_name' => 'Luxe Beauty Market',
            'site_tagline' => '100% Genuine Cosmetics & Skincare Marketplace',
            'site_description' => 'Pakistan’s premier multi-brand luxury cosmetics and skincare marketplace delivering original, verified beauty essentials to your doorstep.',
            'contact_email' => 'support@luxemarket.pk',
            'contact_phone' => '+92 (300) 123-4567',
            'support_whatsapp' => '+923001234567',
            'office_address' => 'Floor 3, Luxury Plaza, MM Alam Road, Gulberg III, Lahore, Pakistan',
            'currency_code' => 'PKR',
            'currency_symbol' => 'PKR',
            'timezone' => 'Asia/Karachi',

            // E-Commerce & Shipping
            'shipping_fee_standard' => '250',
            'shipping_free_threshold' => '5000',
            'shipping_free_enabled' => '1',
            'shipping_carrier_name' => 'TCS / Leopard Express',
            'shipping_estimated_days' => '2 - 4 Business Days',
            'cash_on_delivery_enabled' => '1',
            'online_payment_enabled' => '1',
            'allow_customer_reviews' => '1',

            // Social Channels
            'social_instagram' => 'https://instagram.com/luxemarket.pk',
            'social_facebook' => 'https://facebook.com/luxemarket.pk',
            'social_tiktok' => 'https://tiktok.com/@luxemarket.pk',
            'social_youtube' => '',

            // System
            'maintenance_mode' => '0',
            'maintenance_message' => 'We are undergoing scheduled platform upgrades. We will be back online shortly.',
        ];

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => 'general', 'type' => 'string']
            );
        }
    }
}
