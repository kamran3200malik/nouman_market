<?php

namespace App\Notifications;

use App\Models\ArtistProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ArtistSuspended extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ArtistProfile $artistProfile,
        public string $reason
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Important Notice: Your Artist Account Has Been Suspended')
            ->greeting('Hello ' . $notifiable->name)
            ->line('Your salon artist account on BeautyBook has been suspended by administration.')
            ->line('Reason for suspension: ' . $this->reason)
            ->line('During suspension, your services and studio profile are hidden from the public marketplace, and new bookings are paused.')
            ->action('View Dashboard & Details', route('artist.dashboard'))
            ->line('If you believe this is a mistake or wish to appeal this decision, please contact our platform support team.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [
            'title' => 'Account Suspended',
            'message' => 'Your artist account has been suspended. Reason: ' . $this->reason,
            'type' => 'warning',
            'artist_profile_id' => $this->artistProfile->id,
            'action_url' => route('artist.dashboard'),
        ];
    }
}
