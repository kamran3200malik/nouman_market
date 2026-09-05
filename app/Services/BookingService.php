<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingStatusHistory;
use App\Models\ArtistProfile;
use App\Models\Service;
use App\Models\Payment;
use App\BookingStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class BookingService
{
    public function createBooking(array $data): Booking
    {
        return DB::transaction(function () use ($data) {
            $artistProfile = ArtistProfile::findOrFail($data['artist_profile_id']);
            $service = Service::findOrFail($data['service_id']);

            // Validate artist is approved and active
            if ($artistProfile->approval_status !== 'approved' || !$artistProfile->is_active) {
                throw new \Exception('This artist is not available for booking.');
            }

            // Validate service belongs to artist and is active
            if ($service->artist_profile_id !== $artistProfile->id || !$service->is_active) {
                throw new \Exception('This service is not available for booking.');
            }

            // Validate service type availability
            if ($data['service_type'] === 'home' && !$artistProfile->home_service_available) {
                throw new \Exception('This artist does not provide home services.');
            }

            if ($data['service_type'] === 'salon' && !$service->salon_service_available) {
                throw new \Exception('This service is not available at the salon.');
            }

            // Check for overlapping bookings
            $this->checkOverlappingBookings(
                $artistProfile,
                $data['booking_date'],
                $data['booking_time'],
                $data['duration_minutes']
            );

            // Calculate total price
            $totalPrice = $service->discount_price ?? $service->price;
            if ($data['service_type'] === 'home') {
                $totalPrice += $artistProfile->home_service_fee;
            }

            // Create booking
            $booking = Booking::create([
                'customer_id' => Auth::id(),
                'artist_profile_id' => $artistProfile->id,
                'service_id' => $service->id,
                'booking_number' => 'BK-' . strtoupper(uniqid()),
                'booking_date' => $data['booking_date'],
                'booking_time' => $data['booking_time'],
                'duration_minutes' => $data['duration_minutes'],
                'total_amount' => $totalPrice,
                'service_type' => $data['service_type'],
                'customer_address' => $data['customer_address'] ?? null,
                'customer_area' => $data['customer_area'] ?? null,
                'customer_latitude' => $data['customer_latitude'] ?? null,
                'customer_longitude' => $data['customer_longitude'] ?? null,
                'customer_notes' => $data['customer_notes'] ?? null,
                'status' => BookingStatus::PENDING->value,
            ]);

            // Create status history
            BookingStatusHistory::create([
                'booking_id' => $booking->id,
                'to_status' => BookingStatus::PENDING->value,
                'changed_by' => Auth::id(),
            ]);

            // Create payment record
            $artist = $booking->artistProfile;
            $rate = $artist ? (float) ($artist->commission_rate ?? 10) : 10;
            $commission = round(($totalPrice * $rate) / 100, 2);
            $netAmount = max(0, $totalPrice - $commission);

            Payment::create([
                'booking_id' => $booking->id,
                'amount' => $totalPrice,
                'commission_amount' => $commission,
                'net_amount' => $netAmount,
                'status' => 'pending',
                'payment_method' => $data['payment_method'] ?? 'cash',
            ]);

            return $booking;
        });
    }

    public function updateBookingStatus(Booking $booking, string $status, ?string $notes = null): Booking
    {
        $newStatus = BookingStatus::from($status);

        // Validate status transition
        $currentStatus = BookingStatus::from($booking->status);
        if (!$currentStatus->canTransitionTo($newStatus)) {
            throw new \Exception('Invalid status transition.');
        }

        return DB::transaction(function () use ($booking, $newStatus, $notes) {
            // Update booking status
            $booking->update([
                'status' => $newStatus->value,
            ]);

            // Create status history
            BookingStatusHistory::create([
                'booking_id' => $booking->id,
                'from_status' => $booking->status,
                'to_status' => $newStatus->value,
                'changed_by' => Auth::id(),
                'notes' => $notes,
            ]);

            // Handle completion - update artist rating
            if ($newStatus === BookingStatus::COMPLETED) {
                $this->updateArtistRating($booking->artist_profile_id);
            }

            return $booking;
        });
    }

    public function rescheduleBooking(Booking $booking, string $date, string $time, ?string $notes = null): Booking
    {
        return DB::transaction(function () use ($booking, $date, $time, $notes) {
            // Check for overlapping bookings
            $this->checkOverlappingBookings(
                $booking->artistProfile,
                $date,
                $time,
                $booking->duration_minutes,
                $booking->id
            );

            // Update booking
            $booking->update([
                'booking_date' => $date,
                'booking_time' => $time,
                'status' => BookingStatus::RESCHEDULED->value,
            ]);

            // Create status history
            BookingStatusHistory::create([
                'booking_id' => $booking->id,
                'from_status' => $booking->status,
                'to_status' => BookingStatus::RESCHEDULED->value,
                'changed_by' => Auth::id(),
                'notes' => $notes,
            ]);

            return $booking;
        });
    }

    private function checkOverlappingBookings(ArtistProfile $artist, string $date, string $time, int $duration, ?int $excludeBookingId = null): void
    {
        $startTime = Carbon::parse($date . ' ' . $time);
        $endTime = $startTime->copy()->addMinutes($duration);

        $query = Booking::where('artist_profile_id', $artist->id)
            ->where('booking_date', $date)
            ->whereIn('status', [BookingStatus::CONFIRMED->value, BookingStatus::RESCHEDULED->value]);

        if ($excludeBookingId) {
            $query->where('id', '!=', $excludeBookingId);
        }

        $existingBookings = $query->get();

        foreach ($existingBookings as $existing) {
            $dStr = Carbon::parse($existing->booking_date)->format('Y-m-d');
            $tStr = $existing->booking_time ? substr($existing->booking_time, 0, 8) : '00:00:00';
            $existingStart = Carbon::parse("{$dStr} {$tStr}");
            $existingEnd = $existingStart->copy()->addMinutes($existing->duration_minutes ?: 45);

            if ($startTime < $existingEnd && $endTime > $existingStart) {
                throw new \Exception('This time slot is already booked. Please choose a different time.');
            }
        }
    }

    private function updateArtistRating(int $artistProfileId): void
    {
        $artist = ArtistProfile::find($artistProfileId);
        $reviews = $artist->reviews()->where('is_approved', true);

        $artist->update([
            'rating_avg' => $reviews->avg('rating') ?? 0,
            'review_count' => $reviews->count(),
        ]);
    }

    public function getAvailableDates(ArtistProfile $artist, int $days = 30): array
    {
        $today = Carbon::now()->startOfDay();
        $availableDates = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $today->copy()->addDays($i);
            $dayOfWeek = $date->dayOfWeek;

            // Check if it's a holiday
            $isHoliday = $artist->holidays()->where('date', $date->toDateString())->exists();

            // Check custom availability
            $customAvailability = $artist->customAvailabilities()
                ->where('date', $date->toDateString())
                ->first();

            if ($customAvailability) {
                if ($customAvailability->is_available) {
                    $availableDates[] = [
                        'date' => $date->toDateString(),
                        'slots' => [
                            [
                                'start' => $customAvailability->start_time,
                                'end' => $customAvailability->end_time,
                            ]
                        ]
                    ];
                }
                continue;
            }

            if ($isHoliday) {
                continue;
            }

            // Check regular availability
            $availability = $artist->availabilities()->where('day_of_week', $dayOfWeek)->first();

            if ($availability && $availability->is_available) {
                $slots = [];
                if (!empty($availability->time_slots) && (is_array($availability->time_slots) || is_object($availability->time_slots))) {
                    foreach ($availability->time_slots as $slot) {
                        $slots[] = [
                            'start' => $slot['start'] ?? '09:00',
                            'end' => $slot['end'] ?? '18:00',
                        ];
                    }
                } elseif ($availability->start_time && $availability->end_time) {
                    $slots[] = [
                        'start' => substr($availability->start_time, 0, 5),
                        'end' => substr($availability->end_time, 0, 5),
                    ];
                } else {
                    $slots[] = [
                        'start' => '09:00',
                        'end' => '19:00',
                    ];
                }

                $availableDates[] = [
                    'date' => $date->toDateString(),
                    'slots' => $slots,
                ];
            }
        }

        return $availableDates;
    }
}
