<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ArtistMessage extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $artistName,
        public readonly string $message,
        public readonly string $conversationId,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Message from ' . $this->artistName)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('You have received a new message from ' . $this->artistName . '.')
            ->line('Message: ' . $this->message)
            ->action('View Message', route('customer.messages.show', $this->conversationId))
            ->line('Reply to continue the conversation.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'artist_name' => $this->artistName,
            'message' => $this->message,
            'conversation_id' => $this->conversationId,
            'type' => 'artist_message',
        ];
    }
}
