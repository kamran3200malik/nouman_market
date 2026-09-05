<?php

namespace Database\Factories;

use App\Models\ArtistProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ArtistProfile>
 */
class ArtistProfileFactory extends Factory
{
    protected $model = ArtistProfile::class;

    public function definition(): array
    {
        $name = fake()->company() . ' Salon';
        return [
            'user_id' => User::factory(),
            'business_name' => $name,
            'slug' => Str::slug($name) . '-' . fake()->unique()->numberBetween(100, 9999),
            'professional_type' => 'makeup_artist',
            'years_of_experience' => fake()->numberBetween(1, 15),
            'bio' => fake()->paragraph(),
            'full_description' => fake()->text(),
            'specializations' => ['bridal_makeup', 'hair_styling'],
            'languages' => ['English', 'Urdu'],
            'approval_status' => 'approved',
            'rating_avg' => 5.0,
            'review_count' => 0,
            'is_featured' => false,
            'is_verified' => true,
            'is_active' => true,
            'home_service_available' => true,
            'home_service_fee' => 500,
        ];
    }
}
