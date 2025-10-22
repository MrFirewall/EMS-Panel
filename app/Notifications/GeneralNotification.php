<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast; // Wichtig für Echtzeit
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Broadcasting\PrivateChannel; // Für private Kanäle

class GeneralNotification extends Notification implements ShouldBroadcast // <- Hinzugefügt
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
        // Wir speichern in der Datenbank UND senden über den Broadcaster
        return ['database', 'broadcast']; // <- Geändert
    }

    /**
     * Get the array representation of the notification.
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
     * Definiert den Kanal, über den die Benachrichtigung gesendet wird.
     *
     * @param object $notifiable
     * @return array
     */
    public function broadcastOn(object $notifiable): array
    {
        // Wir broadcasten auf den privaten Kanal des spezifischen Benutzers.
        // Die Autorisierung dieses Kanals erfolgt in routes/channels.php.
        return [
            new PrivateChannel('users.' . $notifiable->id),
        ];
    }

    /**
     * Definiert die zu sendenden Daten für das Broadcasting.
     *
     * Laravel verwendet standardmäßig die toArray()-Methode, aber wir können 
     * explizit toBroadcast() definieren, um nur die notwendigen Daten zu senden,
     * was wir hier tun, indem wir die gleichen Daten wie toDatabase() verwenden.
     *
     * @param object $notifiable
     * @return array
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
