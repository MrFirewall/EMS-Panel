<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class GeneralNotification extends Notification implements ShouldBroadcastNow
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

    public function broadcastOn($notifiable)
    {
        info('Broadcasting to user: ' . $notifiable->id);

        return [
            new PrivateChannel('users.' . $notifiable->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'new.ems.notification';
    }

    public function toArray($notifiable): array
    {
        return [
            'id' => uniqid(), // Eindeutige ID
            'text' => $this->text,
            'icon' => $this->icon,
            'url' => $this->url,
        ];
    }
}
