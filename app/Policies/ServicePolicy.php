<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Service;

class ServicePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Service $service): bool
    {
        return $service->is_active || $user->hasRole('admin') || ($user->hasRole('artist') && $user->artistProfile->id === $service->artist_profile_id);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('artist') && $user->artistProfile && $user->artistProfile->approval_status === 'approved';
    }

    public function update(User $user, Service $service): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->hasRole('artist') && $user->artistProfile->id === $service->artist_profile_id;
    }

    public function delete(User $user, Service $service): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->hasRole('artist') && $user->artistProfile->id === $service->artist_profile_id;
    }
}
