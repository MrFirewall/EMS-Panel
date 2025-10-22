<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

class GeneralNotification extends Notification implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use Queueable;

    protected string $text;
    protected string $icon;
    protected string $url;
    protected int $userId;

    public function __construct(int $userId, string $text, string $icon, string $url)
    {
        $this->userId = $userId; // User-ID für private Channels
        $this->text = $text;
        $this->icon = $icon;
        $this->url = $url;
    }

    /**
     * Welche Kanäle verwendet werden.
     */
    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Channels für Broadcasts (Laravel 12-kompatibel)
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('users.' . $this->userId),
        ];
    }

    /**
     * Broadcast-Eventname
     */
    public function broadcastAs(): string
    {
        return 'new.ems.notification';
    }

    /**
     * Payload für Broadcast & Datenbank
     */
    public function toArray($notifiable): array
    {
        return [
            'text' => $this->text,
            'icon' => $this->icon,
            'url'  => $this->url,
        ];
    }
}
