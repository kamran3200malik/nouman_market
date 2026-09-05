<?php

namespace Tests\Feature;

use App\Models\ArtistProfile;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'customer']);
        Role::firstOrCreate(['name' => 'artist']);
        Role::firstOrCreate(['name' => 'admin']);
    }

    public function test_customer_can_create_booking(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $artist = ArtistProfile::factory()->create(['approval_status' => 'approved', 'is_active' => true]);
        $service = Service::factory()->create([
            'artist_profile_id' => $artist->id,
            'is_active' => true,
            'salon_service_available' => true,
        ]);

        $response = $this->actingAs($customer)
            ->post(route('bookings.store'), [
                'artist_profile_id' => $artist->id,
                'service_id' => $service->id,
                'date' => now()->addDays(3)->toDateString(),
                'time' => '10:00',
                'service_type' => 'salon',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('bookings', [
            'customer_id' => $customer->id,
            'artist_profile_id' => $artist->id,
            'service_id' => $service->id,
            'status' => 'pending',
        ]);
    }

    public function test_artist_can_accept_booking(): void
    {
        $artistUser = User::factory()->create();
        $artistUser->assignRole('artist');

        $artist = ArtistProfile::factory()->create([
            'user_id' => $artistUser->id,
            'approval_status' => 'approved',
            'is_active' => true,
        ]);
        $booking = Booking::factory()->create([
            'artist_profile_id' => $artist->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($artistUser)
            ->post(route('artist.bookings.update-status', $booking->id), [
                'status' => 'confirmed',
            ]);

        $response->assertRedirect();
        $this->assertEquals('confirmed', $booking->fresh()->status);
    }

    public function test_artist_can_reject_booking(): void
    {
        $artistUser = User::factory()->create();
        $artistUser->assignRole('artist');

        $artist = ArtistProfile::factory()->create([
            'user_id' => $artistUser->id,
            'approval_status' => 'approved',
            'is_active' => true,
        ]);
        $booking = Booking::factory()->create([
            'artist_profile_id' => $artist->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($artistUser)
            ->post(route('artist.bookings.update-status', $booking->id), [
                'status' => 'rejected',
            ]);

        $response->assertRedirect();
        $this->assertEquals('rejected', $booking->fresh()->status);
    }

    public function test_customer_can_cancel_booking(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($customer)
            ->post(route('customer.bookings.cancel', $booking->id));

        $response->assertRedirect();
        $this->assertEquals('cancelled', $booking->fresh()->status);
    }

    public function test_cannot_book_with_unapproved_artist(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $artist = ArtistProfile::factory()->create(['approval_status' => 'pending']);
        $service = Service::factory()->create(['artist_profile_id' => $artist->id]);

        $response = $this->actingAs($customer)
            ->post(route('bookings.store'), [
                'artist_profile_id' => $artist->id,
                'service_id' => $service->id,
                'date' => now()->addDays(3)->toDateString(),
                'time' => '10:00',
                'service_type' => 'salon',
            ]);

        $response->assertSessionHasErrors();
    }

    public function test_requires_authentication_to_book(): void
    {
        $artist = ArtistProfile::factory()->create(['approval_status' => 'approved']);
        $service = Service::factory()->create(['artist_profile_id' => $artist->id]);

        $response = $this->post(route('bookings.store'), [
            'artist_profile_id' => $artist->id,
            'service_id' => $service->id,
            'date' => now()->addDays(3)->toDateString(),
            'time' => '10:00',
            'service_type' => 'salon',
        ]);

        $response->assertRedirect(route('login'));
    }
}
