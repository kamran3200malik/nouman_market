<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCreated extends Notification implements ShouldQueue
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
            ->subject('New Booking Request Received')
            ->greeting('Hello ' . $notifiable->name)
            ->line('You have received a new booking request.')
            ->line('Service: ' . $this->booking->service->name)
            ->line('Date: ' . $this->booking->booking_date->format('F j, Y'))
            ->line('Time: ' . $this->booking->booking_time)
            ->line('Customer: ' . $this->booking->customer->name)
            ->action('View Booking', route('artist.bookings.show', $this->booking))
            ->line('Please review and accept or reject this booking.');
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'New Booking Request',
            'message' => 'New booking request from ' . $this->booking->customer->name,
            'type' => 'info',
            'booking_id' => $this->booking->id,
        ];
    }
}
