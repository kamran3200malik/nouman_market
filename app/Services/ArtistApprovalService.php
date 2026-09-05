<?php

namespace App\Services;

use App\Models\ArtistProfile;
use App\Models\ArtistDocument;
use App\Models\User;
use App\ApprovalStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class ArtistApprovalService
{
    public function approveArtist(ArtistProfile $artistProfile, ?string $notes = null): ArtistProfile
    {
        return DB::transaction(function () use ($artistProfile, $notes) {
            $artistProfile->update([
                'approval_status' => ApprovalStatus::APPROVED->value,
                'is_active' => true,
                'approved_at' => now(),
                'rejection_reason' => null,
            ]);

            // Send notification to artist
            Notification::send($artistProfile->user, new \App\Notifications\ArtistApproved($artistProfile));

            return $artistProfile;
        });
    }

    public function rejectArtist(ArtistProfile $artistProfile, string $reason): ArtistProfile
    {
        return DB::transaction(function () use ($artistProfile, $reason) {
            $artistProfile->update([
                'approval_status' => ApprovalStatus::REJECTED->value,
                'is_active' => false,
                'rejection_reason' => $reason,
            ]);

            // Send notification to artist
            Notification::send($artistProfile->user, new \App\Notifications\ArtistRejected($artistProfile, $reason));

            return $artistProfile;
        });
    }

    public function suspendArtist(ArtistProfile $artistProfile, string $reason): ArtistProfile
    {
        return DB::transaction(function () use ($artistProfile, $reason) {
            $artistProfile->update([
                'approval_status' => ApprovalStatus::SUSPENDED->value,
                'is_active' => false,
                'rejection_reason' => $reason,
            ]);

            // Send notification to artist
            Notification::send($artistProfile->user, new \App\Notifications\ArtistSuspended($artistProfile, $reason));

            return $artistProfile;
        });
    }

    public function activateArtist(ArtistProfile $artistProfile): ArtistProfile
    {
        return DB::transaction(function () use ($artistProfile) {
            $artistProfile->update([
                'approval_status' => ApprovalStatus::APPROVED->value,
                'is_active' => true,
                'rejection_reason' => null,
            ]);

            // Send notification to artist
            Notification::send($artistProfile->user, new \App\Notifications\ArtistActivated($artistProfile));

            return $artistProfile;
        });
    }

    public function submitForApproval(User $user, array $profileData, array $documents = []): ArtistProfile
    {
        return DB::transaction(function () use ($user, $profileData, $documents) {
            // Create or update artist profile
            $artistProfile = ArtistProfile::updateOrCreate(
                ['user_id' => $user->id],
                array_merge($profileData, [
                    'approval_status' => ApprovalStatus::PENDING->value,
                    'is_active' => false,
                ])
            );

            // Handle document uploads
            foreach ($documents as $documentType => $file) {
                $path = $file->store('artists/documents', 'private');

                ArtistDocument::updateOrCreate(
                    [
                        'artist_profile_id' => $artistProfile->id,
                        'document_type' => $documentType,
                    ],
                    [
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'file_size' => $file->getSize(),
                        'file_type' => $file->getMimeType(),
                    ]
                );
            }

            // Send notification to admin
            try {
                $admins = User::role('admin')->get();
                if ($admins->isNotEmpty()) {
                    Notification::send($admins, new \App\Notifications\NewArtistApplication($artistProfile));
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Failed to send admin notification on artist registration: ' . $e->getMessage());
            }

            return $artistProfile;
        });
    }
}
