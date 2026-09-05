<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Booking;

class BookingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('artist') || $user->hasRole('customer');
    }

    public function view(User $user, Booking $booking): bool
    {
        // Admin can view all
        if ($user->hasRole('admin')) {
            return true;
        }

        // Artist can view own bookings
        if ($user->hasRole('artist') && $user->artistProfile?->id === $booking->artist_profile_id) {
            return true;
        }

        // Customer can view own bookings
        if ($user->id === $booking->customer_id) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('customer');
    }

    public function update(User $user, Booking $booking): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Booking $booking): bool
    {
        return $user->hasRole('admin');
    }

    public function updateStatus(User $user, Booking $booking): bool
    {
        // Admin can update all statuses
        if ($user->hasRole('admin')) {
            return true;
        }

        // Artist can update status for own bookings
        if ($user->hasRole('artist') && $user->artistProfile?->id === $booking->artist_profile_id) {
            return true;
        }

        // Customer can cancel own bookings
        if ($user->hasRole('customer') && $user->id === $booking->customer_id) {
            return in_array($booking->status, ['pending', 'confirmed']);
        }

        return false;
    }

    public function cancel(User $user, Booking $booking): bool
    {
        // Admin can cancel all
        if ($user->hasRole('admin')) {
            return true;
        }

        // Artist can cancel own bookings
        if ($user->hasRole('artist') && $user->artistProfile?->id === $booking->artist_profile_id) {
            return in_array($booking->status, ['pending', 'confirmed', 'rescheduled']);
        }

        // Customer can cancel own bookings
        if ($user->id === $booking->customer_id) {
            return in_array($booking->status, ['pending', 'confirmed', 'rescheduled']);
        }

        return false;
    }

    public function reschedule(User $user, Booking $booking): bool
    {
        // Admin can reschedule all
        if ($user->hasRole('admin')) {
            return true;
        }

        // Artist can reschedule own bookings
        if ($user->hasRole('artist') && $user->artistProfile?->id === $booking->artist_profile_id) {
            return in_array($booking->status, ['confirmed', 'rescheduled']);
        }

        // Customer can reschedule own bookings
        if ($user->hasRole('customer') && $user->id === $booking->customer_id) {
            return in_array($booking->status, ['pending', 'confirmed']);
        }

        return false;
    }
}
