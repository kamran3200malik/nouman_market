<?php

namespace Database\Factories;

use App\Models\ArtistProfile;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        return [
            'customer_id' => User::factory(),
            'artist_profile_id' => ArtistProfile::factory(),
            'service_id' => Service::factory(),
            'booking_number' => 'BK-' . strtoupper(uniqid()),
            'booking_date' => now()->addDays(fake()->numberBetween(1, 14))->toDateString(),
            'booking_time' => fake()->randomElement(['10:00', '12:00', '14:00', '16:00', '18:00']),
            'duration_minutes' => 60,
            'service_type' => 'salon',
            'status' => 'pending',
            'total_amount' => 2500,
            'advance_amount' => 0,
            'commission_amount' => 250,
            'payment_status' => 'pending',
        ];
    }
}
