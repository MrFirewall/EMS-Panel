<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Broadcasting\PrivateChannel;

// Wichtig: ShouldBroadcast wird durch die via-Methode impliziert, daher nur die Verträge
// und Traits, die unbedingt notwendig sind.
class GeneralNotification extends Notification implements ShouldDispatchAfterCommit
{
    // CastsNotifications entfernt, da es in dieser Version nicht gefunden wird
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
        // $this->notifiable wird automatisch gesetzt.
        return [
            new PrivateChannel('users.' . $this->notifiable->id),
        ];
    }

    /**
     * Setzt den Broadcast-Namen auf einen einfachen, eindeutigen String.
     * Echo kann diesen leichter identifizieren.
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
        // Wir senden alle Daten, die wir in die DB schreiben, direkt über den Broadcast.
        // Das Frontend fetcht die ID, aber wir senden alle nützlichen Daten mit.
        return $this->toDatabase($notifiable);
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
    
    // toArray ist nicht zwingend für Broadcast/Database nötig, bleibt aber als Standard-Methode
    public function toArray($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
