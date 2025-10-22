<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow; 
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit; 
use Illuminate\Queue\SerializesModels;

/**
 * Eine allgemeine Benachrichtigung, die an die Datenbank gesendet und sofort 
 * über einen Broadcast-Treiber (z.B. Reverb/Pusher) an den Client gepusht wird.
 *
 * Implementiert ShouldDispatchAfterCommit, um Race Conditions bei DB-Transaktionen zu vermeiden.
 */
class GeneralNotification extends Notification implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use Queueable, SerializesModels; 

    /**
     * Der anzuzeigende Benachrichtigungstext.
     * @var string
     */
    protected $text;
    
    /**
     * Das Font Awesome Icon für die Anzeige im Frontend.
     * @var string
     */
    protected $icon;
    
    /**
     * Die URL, auf die der Benutzer bei Klick weitergeleitet wird.
     * @var string
     */
    protected $url;

    /**
     * Erstellt eine neue Benachrichtigungsinstanz.
     *
     * @param string $text Der Benachrichtigungstext.
     * @param string $icon Das zu verwendende Icon (z.B. 'fas fa-ambulance').
     * @param string $url Die Ziel-URL der Benachrichtigung.
     */
    public function __construct(string $text, string $icon, string $url)
    {
        $this->text = $text;
        $this->icon = $icon;
        $this->url = $url;
    }

    /**
     * Holt die Benachrichtigungskanäle.
     *
     * @param  mixed  $notifiable
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        // 'database' speichert die Benachrichtigung persistent.
        // 'broadcast' sendet sie über den WebSocket-Treiber.
        return ['database', 'broadcast'];
    }

    /**
     * Holt die Kanäle, auf denen die Benachrichtigung übertragen werden soll.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel|\Illuminate\Broadcasting\PrivateChannel>
     */
    public function broadcastOn($notifiable): array
    {
        // Sendet die Benachrichtigung an den privaten Kanal des spezifischen Benutzers.
        return [
            new PrivateChannel('users.' . $this->notifiable->id),
        ];
    }
    
    /**
     * Setzt den eindeutigen Broadcast-Namen.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'new.ems.notification';
    }
    
    /**
     * Holt die Array-Repräsentation der Benachrichtigung (für Datenbank und Broadcast).
     *
     * @param  mixed  $notifiable
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        // Die Notification-ID wird hier hinzugefügt, damit der Client sie
        // im Broadcast-Payload empfängt und zur Identifikation nutzen kann.
        return [
            'id'   => $this->id, 
            'text' => $this->text, 
            'icon' => $this->icon, 
            'url'  => $this->url,
        ];
    }
}