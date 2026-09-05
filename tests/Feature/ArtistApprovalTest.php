<?php

namespace Tests\Feature;

use App\Models\ArtistProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ArtistApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'artist']);
    }

    public function test_admin_can_approve_artist(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $artist = ArtistProfile::factory()->create(['approval_status' => 'pending']);

        $response = $this->actingAs($admin)
            ->post(route('admin.artists.approve', $artist->id));

        $response->assertRedirect();
        $this->assertEquals('approved', $artist->fresh()->approval_status);
    }

    public function test_admin_can_reject_artist(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $artist = ArtistProfile::factory()->create(['approval_status' => 'pending']);

        $response = $this->actingAs($admin)
            ->post(route('admin.artists.reject', $artist->id), [
                'reason' => 'Incomplete documentation',
            ]);

        $response->assertRedirect();
        $this->assertEquals('rejected', $artist->fresh()->approval_status);
    }

    public function test_admin_can_suspend_artist(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $artist = ArtistProfile::factory()->create(['approval_status' => 'approved']);

        $response = $this->actingAs($admin)
            ->post(route('admin.artists.suspend', $artist->id), [
                'reason' => 'Violation of platform terms',
            ]);

        $response->assertRedirect();
        $this->assertEquals('suspended', $artist->fresh()->approval_status);
    }

    public function test_non_admin_cannot_approve_artist(): void
    {
        $regularUser = User::factory()->create();
        $artist = ArtistProfile::factory()->create(['approval_status' => 'pending']);

        $response = $this->actingAs($regularUser)
            ->post(route('admin.artists.approve', $artist->id));

        $response->assertStatus(403);
        $this->assertEquals('pending', $artist->fresh()->approval_status);
    }

    public function test_approved_artist_is_publicly_visible(): void
    {
        $artist = ArtistProfile::factory()->create([
            'approval_status' => 'approved',
            'slug' => 'test-artist',
        ]);

        $response = $this->get(route('artists.show', $artist->slug));

        $response->assertStatus(200);
        $response->assertSee($artist->business_name);
    }

    public function test_pending_artist_is_not_publicly_visible(): void
    {
        $artist = ArtistProfile::factory()->create([
            'approval_status' => 'pending',
            'slug' => 'pending-artist',
        ]);

        $response = $this->get(route('artists.show', $artist->slug));

        $response->assertStatus(404);
    }

    public function test_artist_receives_notification_on_approval(): void
    {
        \Illuminate\Support\Facades\Notification::fake();

        $admin = User::factory()->create()->assignRole('admin');
        $artistUser = User::factory()->create();
        $artist = ArtistProfile::factory()->create([
            'user_id' => $artistUser->id,
            'approval_status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.artists.approve', $artist->id));

        \Illuminate\Support\Facades\Notification::assertSentTo(
            $artistUser,
            \App\Notifications\ArtistApproved::class
        );
    }
}
