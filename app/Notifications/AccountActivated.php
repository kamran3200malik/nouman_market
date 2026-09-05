<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountActivated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $customMessage = 'Your account has been activated by administration. You now have full access to explore top salons, book appointments, and shop online.'
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Account is Now Active - BeautyBook')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line($this->customMessage)
            ->action('Explore Salons & Artists', route('artists.index'))
            ->line('Thank you for being part of our beauty community!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Account Activated',
            'message' => $this->customMessage,
            'type' => 'success',
            'action_url' => route('dashboard'),
        ];
    }
}
