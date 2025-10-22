<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow; // WICHTIG: ÄNDERUNG auf NOW
use Illuminate\Notifications\Messages\BroadcastMessage; 
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit; 

class GeneralNotification extends Notification implements ShouldBroadcastNow, ShouldDispatchAfterCommit
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
        // BroadcastNow ignoriert die Queue und sendet sofort
        return ['database', 'broadcast'];
    }

    public function broadcastOn(): array
    {
        // Wir verwenden die Notification-ID für den Kanal.
        return [
            new PrivateChannel('users.' . $this->notifiable->id),
        ];
    }
    
    /**
     * Setzt den Broadcast-Namen auf einen einfachen, eindeutigen String.
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'new.ems.notification';
    }
    
    /**
     * Definiert die zu sendenden Daten für das Broadcasting.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toBroadcast($notifiable): array
    {
        return [
            'id' => $this->id, // Wichtig für den Fetch
            'text' => $this->text, 
            'icon' => $this->icon, 
            'url'  => $this->url,
        ];
    }

    public function toArray($notifiable): array
    {
        return [
            'text' => $this->text,
            'icon' => $this->icon,
            'url'  => $this->url,
        ];
    }
}
