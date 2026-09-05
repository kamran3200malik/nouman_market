<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewReview extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $customerName,
        public readonly int $rating,
        public readonly string $review,
        public readonly string $artistId,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Review Received')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('You have received a new review from ' . $this->customerName . '.')
            ->line('Rating: ' . $this->rating . ' stars')
            ->line('Review: ' . $this->review)
            ->action('View Reviews', route('artist.reviews.index'))
            ->line('Keep up the great work!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'customer_name' => $this->customerName,
            'rating' => $this->rating,
            'review' => $this->review,
            'artist_id' => $this->artistId,
            'type' => 'new_review',
        ];
    }
}
