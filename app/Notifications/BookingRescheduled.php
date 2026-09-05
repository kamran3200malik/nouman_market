<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingRescheduled extends Notification
{
    use Queueable;

    public function __construct(
        public ?\App\Models\Booking $booking = null,
        public ?string $notes = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $serviceName = $this->booking?->service?->name ?? 'Beauty Service';
        $dateStr = $this->booking?->booking_date ? \Carbon\Carbon::parse($this->booking->booking_date)->format('F j, Y') : 'N/A';
        $timeStr = $this->booking?->booking_time ?? 'N/A';

        $mail = (new MailMessage)
            ->subject('Booking Rescheduled')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line("The appointment for {$serviceName} has been rescheduled.")
            ->line("New Date: {$dateStr}")
            ->line("New Time: {$timeStr}");

        if ($this->notes) {
            $mail->line('Notes: ' . $this->notes);
        }

        return $mail->action('View Booking', url('/customer/bookings/' . ($this->booking?->id ?? '')))
            ->line('Thank you for choosing our platform!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'booking_rescheduled',
            'title' => 'Booking Rescheduled',
            'message' => 'Your appointment time has been rescheduled.',
            'booking_id' => $this->booking?->id,
            'notes' => $this->notes,
        ];
    }
}
