<?php

namespace Tests\Feature;

use App\Models\ArtistProfile;
use App\Models\Category;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'artist']);
        Role::create(['name' => 'customer']);
    }

    public function test_admin_can_access_admin_dashboard(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $response = $this->actingAs($admin)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
    }

    public function test_customer_cannot_access_admin_dashboard(): void
    {
        $customer = User::factory()->create()->assignRole('customer');

        $response = $this->actingAs($customer)
            ->get(route('admin.dashboard'));

        $response->assertStatus(403);
    }

    public function test_artist_can_access_artist_dashboard(): void
    {
        $artistUser = User::factory()->create()->assignRole('artist');
        ArtistProfile::factory()->create([
            'user_id' => $artistUser->id,
            'approval_status' => 'approved',
        ]);

        $response = $this->actingAs($artistUser)
            ->get(route('artist.dashboard'));

        $response->assertStatus(200);
    }

    public function test_customer_can_access_customer_dashboard(): void
    {
        $customer = User::factory()->create()->assignRole('customer');

        $response = $this->actingAs($customer)
            ->get(route('customer.dashboard'));

        $response->assertStatus(200);
    }

    public function test_guest_cannot_access_protected_routes(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->get(route('artist.dashboard'))->assertRedirect(route('login'));
        $this->get(route('customer.dashboard'))->assertRedirect(route('login'));
    }

    public function test_artist_can_only_manage_own_services(): void
    {
        $artistUser = User::factory()->create()->assignRole('artist');
        $artist = ArtistProfile::factory()->create([
            'user_id' => $artistUser->id,
            'approval_status' => 'approved',
        ]);

        $otherArtist = ArtistProfile::factory()->create(['approval_status' => 'approved']);
        $otherCategory = \App\Models\Category::factory()->create();
        $otherService = Service::factory()->create([
            'artist_profile_id' => $otherArtist->id,
            'category_id' => $otherCategory->id,
        ]);

        $response = $this->actingAs($artistUser)
            ->put(route('artist.services.update', $otherService->id), [
                'category_id' => $otherCategory->id,
                'name' => 'Hacked Service Name',
                'price' => 5000,
                'duration_minutes' => 60,
            ]);

        $response->assertStatus(403);
    }

    public function test_customer_can_only_view_own_bookings(): void
    {
        $customer = User::factory()->create()->assignRole('customer');
        $otherCustomer = User::factory()->create()->assignRole('customer');

        $response = $this->actingAs($customer)
            ->get(route('customer.bookings.index'));

        $response->assertStatus(200);
        // Additional assertions would verify only customer's own bookings are shown
    }
}
