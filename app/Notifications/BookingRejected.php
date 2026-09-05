<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingRejected extends Notification
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
            ->subject('Booking Request Declined')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line("Your booking request for {$serviceName} was declined by the artist.");

        if ($this->reason) {
            $mail->line('Reason: ' . $this->reason);
        }

        return $mail->action('Explore Other Services', url('/services'))
            ->line('Thank you for choosing our platform!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'booking_rejected',
            'title' => 'Booking Declined',
            'message' => 'Your booking request was declined by the artist.',
            'booking_id' => $this->booking?->id,
            'reason' => $this->reason,
        ];
    }
}
