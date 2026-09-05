<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Review;

class ReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Review $review): bool
    {
        return $review->is_approved || $user->hasRole('admin') || $user->id === $review->customer_id || ($user->hasRole('artist') && $user->artistProfile->id === $review->artist_profile_id);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('customer');
    }

    public function update(User $user, Review $review): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->id === $review->customer_id;
    }

    public function delete(User $user, Review $review): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->id === $review->customer_id;
    }

    public function approve(User $user, Review $review): bool
    {
        return $user->hasRole('admin') && $user->can('manage reviews');
    }
}
