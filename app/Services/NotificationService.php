<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\ArtistProfile;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    public function sendBookingCreatedNotification(Booking $booking): void
    {
        // Notify artist
        $artist = $booking->artistProfile->user;
        Notification::send($artist, new \App\Notifications\BookingCreated($booking));

        // Notify customer
        $customer = $booking->customer;
        Notification::send($customer, new \App\Notifications\BookingCreatedCustomer($booking));
    }

    public function sendBookingConfirmedNotification(Booking $booking): void
    {
        $customer = $booking->customer;
        Notification::send($customer, new \App\Notifications\BookingConfirmed($booking));
    }

    public function sendBookingRejectedNotification(Booking $booking, string $reason): void
    {
        $customer = $booking->customer;
        Notification::send($customer, new \App\Notifications\BookingRejected($booking, $reason));
    }

    public function sendBookingCancelledNotification(Booking $booking, string $reason): void
    {
        $artist = $booking->artistProfile->user;
        $customer = $booking->customer;

        Notification::send($artist, new \App\Notifications\BookingCancelled($booking, $reason));
        Notification::send($customer, new \App\Notifications\BookingCancelledCustomer($booking, $reason));
    }

    public function sendBookingRescheduledNotification(Booking $booking): void
    {
        $artist = $booking->artistProfile->user;
        $customer = $booking->customer;

        Notification::send($artist, new \App\Notifications\BookingRescheduled($booking));
        Notification::send($customer, new \App\Notifications\BookingRescheduledCustomer($booking));
    }

    public function sendAppointmentReminder(Booking $booking, int $hoursBefore): void
    {
        $customer = $booking->customer;
        Notification::send($customer, new \App\Notifications\AppointmentReminder($booking, $hoursBefore));
    }

    public function sendReviewReminder(Booking $booking): void
    {
        $customer = $booking->customer;
        Notification::send($customer, new \App\Notifications\ReviewReminder($booking));
    }

    public function sendNewReviewNotification(Booking $booking): void
    {
        $artist = $booking->artistProfile->user;
        Notification::send($artist, new \App\Notifications\NewReview($booking));
    }

    public function sendPaymentReceivedNotification(Booking $booking): void
    {
        $artist = $booking->artistProfile->user;
        Notification::send($artist, new \App\Notifications\PaymentReceived($booking));
    }

    public function sendPayoutProcessedNotification(\App\Models\Payout $payout): void
    {
        $artist = $payout->artistProfile->user;
        Notification::send($artist, new \App\Notifications\PayoutProcessed($payout));
    }

    public function sendNewMessageNotification(\App\Models\Message $message): void
    {
        $recipient = $message->recipient;
        Notification::send($recipient, new \App\Notifications\NewMessage($message));
    }
}
