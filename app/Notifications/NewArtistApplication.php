<?php

namespace App\Notifications;

use App\Models\ArtistProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewArtistApplication extends Notification implements ShouldQueue
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
            ->subject('New Artist Application for Review')
            ->greeting('Hello Admin')
            ->line('A new artist has submitted an application for approval.')
            ->line('Artist: ' . $this->artistProfile->business_name ?? $this->artistProfile->user->name)
            ->line('Professional Type: ' . ucfirst(str_replace('_', ' ', $this->artistProfile->professional_type)))
            ->action('Review Application', route('admin.artists.show', $this->artistProfile))
            ->line('Please review the application and approve or reject it.');
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'New Artist Application',
            'message' => 'New artist application pending review',
            'type' => 'info',
            'artist_profile_id' => $this->artistProfile->id,
        ];
    }
}
