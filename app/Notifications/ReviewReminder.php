<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Booking $booking
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('How Was Your Experience?')
            ->greeting('Hello ' . $notifiable->name)
            ->line('We hope you enjoyed your recent appointment!')
            ->line('Service: ' . $this->booking->service->name)
            ->line('Artist: ' . $this->booking->artistProfile->business_name ?? $this->booking->artistProfile->user->name)
            ->line('Please take a moment to leave a review and help others make informed decisions.')
            ->action('Leave a Review', route('artists.show', $this->booking->artistProfile->slug))
            ->line('Thank you for choosing our platform!');
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Leave a Review',
            'message' => 'How was your recent appointment? Leave a review!',
            'type' => 'info',
            'booking_id' => $this->booking->id,
        ];
    }
}
