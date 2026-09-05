<?php

namespace App\Notifications;

use App\Models\ArtistProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ArtistApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ArtistProfile $artistProfile
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Congratulations! Your Artist Profile Has Been Approved')
            ->greeting('Hello ' . $notifiable->name)
            ->line('Great news! Your artist profile has been approved and is now live on our platform.')
            ->line('You can now start receiving bookings and showcasing your services to customers.')
            ->action('Open Artist Dashboard', route('artist.dashboard'))
            ->line('Thank you for joining our beauty marketplace!');
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Profile Approved',
            'message' => 'Your artist profile has been approved and is now live.',
            'type' => 'success',
            'artist_profile_id' => $this->artistProfile->id,
        ];
    }
}
