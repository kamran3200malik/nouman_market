<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Platform Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration settings for the beauty salon marketplace platform.
    |
    */

    'commission_percentage' => env('PLATFORM_COMMISSION_PERCENTAGE', 10),

    'currency' => env('PLATFORM_CURRENCY', 'PKR'),

    'currency_symbol' => env('PLATFORM_CURRENCY_SYMBOL', '₨'),

    'booking_rules' => [
        'minimum_hours_before_booking' => env('MINIMUM_HOURS_BEFORE_BOOKING', 2),
        'maximum_days_ahead' => env('MAXIMUM_DAYS_AHEAD', 90),
        'cancellation_hours_before' => env('CANCELLATION_HOURS_BEFORE', 24),
        'reschedule_hours_before' => env('RESCHEDULE_HOURS_BEFORE', 24),
    ],

    'payment' => [
        'supported_methods' => ['cash', 'bank_transfer', 'jazzcash', 'easypaisa', 'stripe'],
        'default_method' => env('DEFAULT_PAYMENT_METHOD', 'cash'),
        'require_payment_before_booking' => env('REQUIRE_PAYMENT_BEFORE_BOOKING', false),
    ],

    'review' => [
        'require_booking_to_review' => true,
        'auto_approve_reviews' => env('AUTO_APPROVE_REVIEWS', false),
        'minimum_rating' => 1,
        'maximum_rating' => 5,
    ],

    'notification' => [
        'appointment_reminder_hours' => [24, 2],
        'review_reminder_hours' => 24,
    ],

    'file_upload' => [
        'max_file_size' => 5120, // 5MB in KB
        'allowed_image_types' => ['jpg', 'jpeg', 'png', 'webp'],
        'allowed_document_types' => ['pdf', 'jpg', 'jpeg', 'png'],
    ],
];
