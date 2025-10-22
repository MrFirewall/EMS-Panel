<?php

namespace App\Notifications;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

class GeneralNotification extends Notification implements ShouldDispatchAfterCommit
{
    use Queueable;

    protected $text;
    protected $icon;
    protected $url;

    public function __construct(string $text, string $icon, string $url)
    {
        $this->text = $text;
        $this->icon = $icon;
        $this->url = $url;
    }

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    // ✅ Correct signature
    public function broadcastOn(): array
    {
        // $this->notifiable is automatically set by Laravel
        return [
            new PrivateChannel('users.' . $this->notifiable->id),
        ];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'text' => $this->text,
            'icon' => $this->icon,
            'url'  => $this->url,
        ];
    }

    public function toBroadcast($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}

