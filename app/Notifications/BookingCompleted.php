<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCompleted extends Notification
{
    use Queueable;

    public string $artistName;
    public string $serviceName;
    public string $bookingId;

    public function __construct(
        \App\Models\Booking|string $bookingOrArtistName,
        ?string $serviceName = null,
        ?string $bookingId = null,
    ) {
        if ($bookingOrArtistName instanceof \App\Models\Booking) {
            $this->artistName = $bookingOrArtistName->artistProfile?->business_name ?? 'Your Beauty Specialist';
            $this->serviceName = $bookingOrArtistName->service?->name ?? 'Beauty Service';
            $this->bookingId = (string) ($bookingOrArtistName->booking_number ?? $bookingOrArtistName->id);
        } else {
            $this->artistName = (string) $bookingOrArtistName;
            $this->serviceName = (string) ($serviceName ?? 'Beauty Service');
            $this->bookingId = (string) ($bookingId ?? '');
        }
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Booking Completed')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your booking for ' . $this->serviceName . ' with ' . $this->artistName . ' has been completed.')
            ->line('Booking ID: ' . $this->bookingId)
            ->line('Please leave a review to help others and improve our service.')
            ->action('Leave a Review', url('/customer/bookings/' . $this->bookingId))
            ->line('Thank you for choosing our platform!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'artist_name' => $this->artistName,
            'service_name' => $this->serviceName,
            'booking_id' => $this->bookingId,
            'type' => 'booking_completed',
            'title' => 'Service Completed',
            'message' => "Your appointment for {$this->serviceName} with {$this->artistName} has been completed.",
        ];
    }
}
