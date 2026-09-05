<?php

namespace Database\Factories;

use App\Models\ArtistProfile;
use App\Models\Category;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        $name = fake()->words(3, true);
        return [
            'artist_profile_id' => ArtistProfile::factory(),
            'category_id' => Category::factory(),
            'name' => ucfirst($name),
            'slug' => Str::slug($name) . '-' . fake()->unique()->numberBetween(100, 9999),
            'description' => fake()->paragraph(),
            'price' => fake()->randomFloat(2, 500, 15000),
            'duration_minutes' => fake()->randomElement([30, 45, 60, 90, 120]),
            'is_active' => true,
            'home_service_available' => true,
            'salon_service_available' => true,
            'booking_required' => true,
            'advance_payment_required' => false,
            'sort_order' => fake()->numberBetween(1, 10),
        ];
    }
}
