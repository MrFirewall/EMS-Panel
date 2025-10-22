<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage; 
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit; // Für Transaktionssicherheit

class GeneralNotification extends Notification implements ShouldBroadcast, ShouldDispatchAfterCommit
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
     * Wir fügen HIER einen Debug-Schritt hinzu.
     *
     * @param mixed $notifiable
     * @return BroadcastMessage
     */
    public function toBroadcast($notifiable): array
    {
        $payload = [
            'id' => $this->id, // Wichtig für den Fetch
            'text' => $this->text, 
            'icon' => $this->icon, 
            'url'  => $this->url,
        ];
        
        // DEBUG: Prüft, ob die Daten korrekt an den Broadcaster übergeben werden.
        // dd('Broadcasting Payload:', $payload, 'Broadcast Name:', $this->broadcastAs()); 

        return $payload;
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