<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBroadcast; // Muss für Broadcasting implementiert werden
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Broadcasting\PrivateChannel; // Für private Benutzerkanäle

class GeneralNotification extends Notification implements ShouldBroadcast // ShouldBroadcast hinzugefügt
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
     * HINWEIS: Die Signatur MUSS kompatibel mit der Basisklasse sein, daher wird 
     * der Typ 'object' für $notifiable weggelassen.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function broadcastOn($notifiable): array
    {
        // Wir broadcasten auf den privaten Kanal des spezifischen Benutzers.
        // Die Autorisierung dieses Kanals erfolgt in routes/channels.php.
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
     * * Wir senden hier die gleichen Daten, damit der fetch-Aufruf weiß, was anzuzeigen ist.
     * Wenn Sie dies nicht definieren, sendet Laravel den toArray()-Output.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toBroadcast($notifiable): array
    {
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
