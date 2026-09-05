<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingConfirmed extends Notification implements ShouldQueue
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
        $dateStr = $this->booking->booking_date ? \Carbon\Carbon::parse($this->booking->booking_date)->format('F j, Y') : 'N/A';
        return (new MailMessage)
            ->subject('Your Booking Has Been Confirmed')
            ->greeting('Hello ' . $notifiable->name)
            ->line('Great news! Your booking has been confirmed by the artist.')
            ->line('Service: ' . ($this->booking->service?->name ?? 'Beauty Service'))
            ->line('Date: ' . $dateStr)
            ->line('Time: ' . ($this->booking->booking_time ?? 'Scheduled Slot'))
            ->line('Total: PKR ' . number_format($this->booking->total_amount ?: $this->booking->total_price ?: 0))
            ->action('View Booking', route('customer.bookings.show', $this->booking))
            ->line('We look forward to seeing you!');
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Booking Confirmed',
            'message' => 'Your booking has been confirmed',
            'type' => 'success',
            'booking_id' => $this->booking->id,
        ];
    }
}
