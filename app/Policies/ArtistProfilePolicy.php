<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ArtistProfile;

class ArtistProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('customer');
    }

    public function view(User $user, ArtistProfile $artistProfile): bool
    {
        // Admin can view all
        if ($user->hasRole('admin')) {
            return true;
        }

        // Artist can view own profile
        if ($user->hasRole('artist') && $user->artistProfile->id === $artistProfile->id) {
            return true;
        }

        // Customers can view approved and active artists
        if ($user->hasRole('customer')) {
            return $artistProfile->approval_status === 'approved' && $artistProfile->is_active;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('artist');
    }

    public function update(User $user, ArtistProfile $artistProfile): bool
    {
        // Admin can update all
        if ($user->hasRole('admin')) {
            return true;
        }

        // Artist can update own profile
        if ($user->hasRole('artist') && $user->artistProfile->id === $artistProfile->id) {
            return true;
        }

        return false;
    }

    public function delete(User $user, ArtistProfile $artistProfile): bool
    {
        return $user->hasRole('admin');
    }

    public function approve(User $user, ArtistProfile $artistProfile): bool
    {
        return $user->hasRole('admin') || $user->can('approve artists');
    }

    public function reject(User $user, ArtistProfile $artistProfile): bool
    {
        return $user->hasRole('admin') || $user->can('reject artists');
    }

    public function suspend(User $user, ArtistProfile $artistProfile): bool
    {
        return $user->hasRole('admin') || $user->can('suspend artists');
    }

    public function activate(User $user, ArtistProfile $artistProfile): bool
    {
        return $user->hasRole('admin') || $user->can('activate artists');
    }

    public function viewDocument(User $user, ArtistProfile $artistProfile): bool
    {
        return $user->hasRole('admin') || ($user->hasRole('artist') && $user->artistProfile->id === $artistProfile->id);
    }
}
