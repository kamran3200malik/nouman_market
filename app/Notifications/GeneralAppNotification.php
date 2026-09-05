<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class GeneralAppNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public string $type = 'info', // 'booking', 'message', 'review', 'system', 'success', 'warning'
        public ?string $actionUrl = null,
        public array $extraData = []
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'action_url' => $this->actionUrl,
            'extra' => $this->extraData,
            'created_at' => now()->toIso8601String(),
        ];
    }
}
