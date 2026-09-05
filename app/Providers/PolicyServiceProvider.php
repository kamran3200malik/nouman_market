<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\ArtistProfile;
use App\Models\Booking;
use App\Models\Service;
use App\Models\Review;
use App\Policies\ArtistProfilePolicy;
use App\Policies\BookingPolicy;
use App\Policies\ServicePolicy;
use App\Policies\ReviewPolicy;

class PolicyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(ArtistProfile::class, ArtistProfilePolicy::class);
        Gate::policy(Booking::class, BookingPolicy::class);
        Gate::policy(Service::class, ServicePolicy::class);
        Gate::policy(Review::class, ReviewPolicy::class);
    }
}
