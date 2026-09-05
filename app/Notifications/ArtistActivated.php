<?php

namespace App\Notifications;

use App\Models\ArtistProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ArtistActivated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ?ArtistProfile $artistProfile = null
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $salonName = $this->artistProfile?->business_name ? " for {$this->artistProfile->business_name}" : '';

        return (new MailMessage)
            ->subject('Your Studio Profile is Active - Welcome Back!')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line("Great news! Your studio account and profile{$salonName} have been activated by administration.")
            ->line('Your services, products, and studio listings are now live on the marketplace, and clients can book appointments with you.')
            ->action('Open Studio Dashboard', route('artist.dashboard'))
            ->line('Thank you for being a valued partner with us!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Account & Studio Activated',
            'message' => 'Your studio profile is now active and accepting client bookings.',
            'type' => 'success',
            'action_url' => route('artist.dashboard'),
            'artist_profile_id' => $this->artistProfile?->id,
        ];
    }
}
