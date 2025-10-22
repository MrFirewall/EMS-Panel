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

    protected int $userId;
    protected string $text;
    protected string $icon;
    protected string $url;

    /**
     * GeneralNotification constructor.
     *
     * @param int $userId ID des Benutzers für den privaten Channel
     * @param string $text Text der Notification
     * @param string $icon Icon für die Notification
     * @param string $url Ziel-URL der Notification
     */
    public function __construct(int $userId, string $text, string $icon, string $url)
    {
        $this->userId = $userId;
        $this->text   = $text;
        $this->icon   = $icon;
        $this->url    = $url;
    }

    /**
     * Kanäle, die für die Notification verwendet werden.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Broadcast-Kanal für den Benutzer.
     *
     * @return array
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('users.' . $this->userId),
        ];
    }

    /**
     * Broadcast-Event-Name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'new.ems.notification';
    }

    /**
     * Payload für Datenbank & Broadcast.
     *
     * @param mixed $notifiable
     * @return array
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
