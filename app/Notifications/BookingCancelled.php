<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCancelled extends Notification
{
    use Queueable;

    public function __construct(
        public ?\App\Models\Booking $booking = null,
        public ?string $reason = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $serviceName = $this->booking?->service?->name ?? 'Beauty Service';
        $mail = (new MailMessage)
            ->subject('Booking Cancelled')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line("The appointment for {$serviceName} has been cancelled.");

        if ($this->reason) {
            $mail->line('Reason: ' . $this->reason);
        }

        return $mail->action('View Bookings', url('/customer/bookings'))
            ->line('Thank you for choosing our platform!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'booking_cancelled',
            'title' => 'Booking Cancelled',
            'message' => 'The appointment has been cancelled.',
            'booking_id' => $this->booking?->id,
            'reason' => $this->reason,
        ];
    }
}
