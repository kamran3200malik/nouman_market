<?php

namespace App\Notifications;

use App\Models\ArtistProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ArtistRejected extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ArtistProfile $artistProfile,
        public string $reason
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Artist Profile Application Status')
            ->greeting('Hello ' . $notifiable->name)
            ->line('We regret to inform you that your artist profile application has been reviewed and rejected.')
            ->line('Reason: ' . $this->reason)
            ->line('You may update your profile and submit it again for review.')
            ->action('View Application Status', route('artist.pending-approval'))
            ->line('If you have any questions, please contact our support team.');
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Profile Rejected',
            'message' => 'Your artist profile was rejected. Reason: ' . $this->reason,
            'type' => 'error',
            'artist_profile_id' => $this->artistProfile->id,
        ];
    }
}
