<?php

namespace Tests\Unit;

use App\Models\ArtistProfile;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    private BookingService $bookingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bookingService = new BookingService();
    }

    public function test_can_create_booking(): void
    {
        $customer = User::factory()->create();
        $artist = ArtistProfile::factory()->create(['approval_status' => 'approved', 'is_active' => true]);
        $service = Service::factory()->create([
            'artist_profile_id' => $artist->id,
            'is_active' => true,
            'salon_service_available' => true,
        ]);

        $this->actingAs($customer);

        $bookingData = [
            'artist_profile_id' => $artist->id,
            'service_id' => $service->id,
            'booking_date' => now()->addDays(3)->toDateString(),
            'booking_time' => '10:00',
            'duration_minutes' => 60,
            'service_type' => 'salon',
        ];

        $booking = $this->bookingService->createBooking($bookingData);

        $this->assertInstanceOf(Booking::class, $booking);
        $this->assertEquals('pending', $booking->status);
        $this->assertEquals($customer->id, $booking->customer_id);
        $this->assertEquals($artist->id, $booking->artist_profile_id);
    }

    public function test_can_update_booking_status(): void
    {
        $artist = ArtistProfile::factory()->create(['approval_status' => 'approved', 'is_active' => true]);
        $service = Service::factory()->create([
            'artist_profile_id' => $artist->id,
            'is_active' => true,
            'salon_service_available' => true,
        ]);
        $booking = Booking::factory()->create([
            'artist_profile_id' => $artist->id,
            'service_id' => $service->id,
            'status' => 'pending',
        ]);

        $updatedBooking = $this->bookingService->updateBookingStatus($booking, 'confirmed');

        $this->assertEquals('confirmed', $updatedBooking->status);
    }

    public function test_can_reschedule_booking(): void
    {
        $artist = ArtistProfile::factory()->create(['approval_status' => 'approved', 'is_active' => true]);
        $service = Service::factory()->create([
            'artist_profile_id' => $artist->id,
            'is_active' => true,
            'salon_service_available' => true,
        ]);
        $booking = Booking::factory()->create([
            'artist_profile_id' => $artist->id,
            'service_id' => $service->id,
            'status' => 'confirmed',
            'booking_date' => now()->addDays(3)->toDateString(),
            'booking_time' => '10:00',
            'duration_minutes' => 60,
        ]);

        $newDate = now()->addDays(5)->toDateString();
        $newTime = '14:00';

        $updatedBooking = $this->bookingService->rescheduleBooking($booking, $newDate, $newTime);

        $this->assertEquals($newDate, $updatedBooking->booking_date->toDateString());
        $this->assertEquals($newTime, $updatedBooking->booking_time);
        $this->assertEquals('rescheduled', $updatedBooking->status);
    }
}
