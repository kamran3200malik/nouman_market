<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceived extends Notification
{
    use Queueable;

    public function __construct(
        public readonly float $amount,
        public readonly string $bookingId,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Payment Received')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('You have received a payment of PKR ' . number_format($this->amount, 0))
            ->line('Booking ID: ' . $this->bookingId)
            ->line('The amount has been added to your earnings.')
            ->action('View Earnings', route('artist.earnings.index'))
            ->line('Thank you for using our platform!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'amount' => $this->amount,
            'booking_id' => $this->bookingId,
            'type' => 'payment_received',
        ];
    }
}
