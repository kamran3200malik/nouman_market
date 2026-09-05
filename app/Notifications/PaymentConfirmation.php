<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentConfirmation extends Notification
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
            ->subject('Payment Confirmation')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your payment of PKR ' . number_format($this->amount, 0) . ' has been confirmed.')
            ->line('Booking ID: ' . $this->bookingId)
            ->line('Thank you for your payment!')
            ->action('View Booking', route('customer.bookings.show', $this->bookingId))
            ->line('We look forward to serving you!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'amount' => $this->amount,
            'booking_id' => $this->bookingId,
            'type' => 'payment_confirmation',
        ];
    }
}
