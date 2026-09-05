<?php

namespace App\Notifications;

use App\Models\ArtistProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionExpired extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ArtistProfile $artistProfile
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
        $plan = $this->artistProfile->subscription_plan_name ?: 'Monthly Subscription';
        $expiryDate = $this->artistProfile->subscription_expires_at ? $this->artistProfile->subscription_expires_at->format('M d, Y') : 'recently';

        return (new MailMessage)
            ->subject('Important Notice: Your Monthly Salon Subscription Has Expired')
            ->greeting('Hello ' . $notifiable->name)
            ->line("Your {$plan} on BeautyBook expired on {$expiryDate}.")
            ->line('As per platform policy, your salon studio profile and treatment listings have been temporarily suspended from the public marketplace, and new bookings are paused.')
            ->line('To reactivate your studio and continue receiving client bookings with 0% commission, please renew your subscription plan.')
            ->action('Access Artist Dashboard', route('artist.dashboard'))
            ->line('If you have questions or need assistance renewing, please contact our support team.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        $plan = $this->artistProfile->subscription_plan_name ?: 'Monthly Subscription';
        $expiryDate = $this->artistProfile->subscription_expires_at ? $this->artistProfile->subscription_expires_at->format('M d, Y') : 'recently';

        return [
            'title' => 'Subscription Expired - Salon Suspended',
            'message' => "Your {$plan} expired on {$expiryDate}. Your salon is temporarily suspended from marketplace listings until renewal.",
            'type' => 'warning',
            'artist_profile_id' => $this->artistProfile->id,
            'action_url' => route('artist.dashboard'),
        ];
    }
}
