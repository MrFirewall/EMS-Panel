<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit; // NEU: Für Transaktionssicherheit
use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\PrivateChannel;
// ShouldBroadcast und den BroadcastMessage Import entfernt, um Signature-Konflikte zu vermeiden

// Wir implementieren nur ShouldDispatchAfterCommit, um Transaktionssicherheit zu gewährleisten.
// Laravel handhabt das Broadcasting automatisch, da 'broadcast' in via() enthalten ist.
class GeneralNotification extends Notification implements ShouldDispatchAfterCommit 
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
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable): array
    {
        // Wir speichern in der Datenbank UND senden per Broadcast
        return ['database', 'broadcast']; 
    }

    /**
     * Definiert den Kanal, über den die Benachrichtigung gesendet wird.
     *
     * Dies wird automatisch von Laravel beim Broadcasting gesucht.
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
     * @param mixed $notifiable
     * @return array<string, mixed>
     */
    public function toDatabase($notifiable): array
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
     * @param mixed $notifiable
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [
            //
        ];
    }
}
