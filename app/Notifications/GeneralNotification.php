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
        // ShouldBroadcastNow sendet sofort und ignoriert die Queue.
        return ['database', 'broadcast'];
    }

    // Wir verwenden die leere Signatur, die den Fatal Error endlich gelöst hat.
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
    
    // WICHTIGE ÄNDERUNG: toBroadcast entfernt, toArray wird stattdessen für Broadcasts verwendet.
    // Dies zwingt Laravel zur robustesten Standard-Broadcast-Logik.
    public function toArray($notifiable): array
    {
        // Wir fügen die ID hier ein, da der Broadcast die Daten als Payload sendet.
        return [
            'id' => $this->id, // Wichtig für den Fetch/Client-Identifikation
            'text' => $this->text, 
            'icon' => $this->icon, 
            'url'  => $this->url,
        ];
    }
}
