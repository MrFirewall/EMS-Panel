<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit; // NEU: Für Transaktionssicherheit
use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\PrivateChannel;

class GeneralNotification extends Notification implements ShouldBroadcast, ShouldDispatchAfterCommit // ShouldDispatchAfterCommit hinzugefügt
{
    use Queueable;

    protected $text;
    protected $icon;
    protected $url;

    /**
     * Create a new notification instance.
     *
     * @param string $text Der Anzeigetext (z.B. "Neuer Urlaubsantrag")
     * @param string $icon Eine FontAwesome-Icon-Klasse (z.B. "fas fa-plane")
     * @param string $url Die URL, zu der man beim Klicken gelangt
     */
    public function __construct(string $text, string $icon, string $url)
    {
        $this->text = $text;
        $this->icon = $icon;
        $this->url = $url;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Wir speichern in der Datenbank UND senden per Broadcast
        return ['database', 'broadcast']; 
    }

    /**
     * Definiert den Kanal, über den die Benachrichtigung gesendet wird.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function broadcastOn($notifiable): array
    {
        // Wir broadcasten auf den privaten Kanal des spezifischen Benutzers.
        return [
            new PrivateChannel('users.' . $notifiable->id),
        ];
    }
    
    /**
     * Get the array representation of the notification (für die Datenbank).
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        // Diese Daten werden in der 'data'-Spalte der 'notifications'-Tabelle gespeichert.
        return [
            'text' => $this->text,
            'icon' => $this->icon,
            'url'  => $this->url,
        ];
    }
    
    /**
     * Definiert die zu sendenden Daten für das Broadcasting.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toBroadcast($notifiable): array
    {
        // Der toBroadcast-Output sollte die gleichen Daten wie toDatabase senden.
        return $this->toDatabase($notifiable);
    }
    
    /**
     * Get the array representation of the notification (wird hier nicht genutzt).
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
