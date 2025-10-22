<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Broadcasting\PrivateChannel;
// Hinzufügen des InteractsWithBroadcasting Trait
use Illuminate\Notifications\CastsNotifications; 

// Wir implementieren nur ShouldDispatchAfterCommit, um Transaktionssicherheit zu gewährleisten.
class GeneralNotification extends Notification implements ShouldDispatchAfterCommit
{
    // Die Basisklasse Illuminate\Notifications\Notification verwendet bereits den Notifiable-Trait.
    // Wir entfernen ShouldBroadcast, da dies durch die via-Methode impliziert wird.
    use Queueable, CastsNotifications; 

    protected $text;
    protected $icon;
    protected $url;

    public function __construct(string $text, string $icon, string $url)
    {
        $this->text = $text;
        $this->icon = $icon;
        $this->url = $url;
    }
    
    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Definiert den Kanal, über den die Benachrichtigung gesendet wird.
     *
     * @return array
     */
    public function broadcastOn(): array
    {
        // Wir verwenden $this->notifiable, da die FatalError-Probleme nur durch den Parameter ausgelöst wurden.
        // Wenn $this->notifiable hier null ist, liegt ein Problem in der Basisklasse vor.
        // ANNAHME: Der notifiable ist jetzt durch das Notification-System korrekt gesetzt.
        return [
            new PrivateChannel('users.' . $this->notifiable->id),
        ];
    }

    /**
     * Setzt den Broadcast-Namen auf einen einfachen, eindeutigen String.
     * Echo kann diesen leichter identifizieren.
     * * @return string
     */
    public function broadcastAs(): string
    {
        return 'new.ems.notification';
    }

    /**
     * Definiert die zu sendenden Daten für das Broadcasting.
     *
     * Wir senden die Datenbankdaten, da diese bereits die Icon/Text/URL-Struktur enthalten.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toBroadcast($notifiable): array
    {
        // Wir senden alle Daten, die wir in die DB schreiben, direkt über den Broadcast.
        return [
            'id' => $this->id,
            'text' => $this->text,
            'icon' => $this->icon,
            'url' => $this->url,
            // Die Benachrichtigungs-ID (UUID) ist entscheidend für den Fetch.
        ];
    }

    /**
     * Get the array representation of the notification (für die Datenbank).
     *
     * @param mixed $notifiable
     * @return array<string, mixed>
     */
    public function toDatabase($notifiable): array
    {
        return [
            'text' => $this->text,
            'icon' => $this->icon,
            'url'  => $this->url,
        ];
    }
    
    // toArray und toBroadcast sind jetzt getrennt, um beide Kanäle korrekt zu bedienen.
    public function toArray($notifiable): array
    {
        return [];
    }
}
