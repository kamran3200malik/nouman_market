<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Booking $booking,
        public int $hoursBefore
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $timeText = $this->hoursBefore === 24 ? 'tomorrow' : 'in ' . $this->hoursBefore . ' hours';

        return (new MailMessage)
            ->subject('Appointment Reminder')
            ->greeting('Hello ' . $notifiable->name)
            ->line('This is a friendly reminder that your appointment is ' . $timeText . '.')
            ->line('Service: ' . $this->booking->service->name)
            ->line('Date: ' . $this->booking->booking_date->format('F j, Y'))
            ->line('Time: ' . $this->booking->booking_time)
            ->action('View Booking', route('customer.bookings.show', $this->booking))
            ->line('We look forward to seeing you!');
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Appointment Reminder',
            'message' => 'Your appointment is in ' . $this->hoursBefore . ' hours',
            'type' => 'info',
            'booking_id' => $this->booking->id,
        ];
    }
}
