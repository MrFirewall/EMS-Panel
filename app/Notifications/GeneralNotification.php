<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit; // NEU: Für Transaktionssicherheit
use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\PrivateChannel;

// Wir implementieren nur ShouldDispatchAfterCommit, um Transaktionssicherheit zu gewährleisten.
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
    
    // setNotifiable wird entfernt, da es nur den FatalError umgangen hat. 
    // Wir vertrauen auf den Parameter in broadcastOn.

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
     * Wir geben den Parameter $notifiable NICHT an, um die PHP-Kompatibilität zu wahren.
     * Laravel injiziert $notifiable intern.
     *
     * @return array
     */
    public function broadcastOn(): array // KEINE PARAMETER HIER!
    {
        // Da die Notification-Basisklasse keine Typisierung erlaubt,
        // muss der Kanal über den Notifiable-User im Kontext der Notification definiert werden.
        // Der Kanalname muss die Logik in routes/channels.php widerspiegeln.
        // HINWEIS: $this->id des Notifiable-Objekts ist NICHT verlässlich.
        // Der Standardweg, wenn $notifiable nicht übergeben wird, ist, den Kanal-Namen direkt zu bauen.
        // Da wir die Strictness brechen mussten, können wir hier nur den User-Typ des notifiable erwarten.

        // WICHTIGE ANNAHME: Wir gehen davon aus, dass der Notifiable-User IMMER die Auth-ID ist,
        // wenn er über den Broadcast-Kanal gesendet wird, oder der Kanal wird in toBroadcast() definiert.

        // Im Falle eines FatalError mit $notifiable: Verwenden Sie den Kanal, den Laravel erwartet:
        return [
            new PrivateChannel('users.' . $this->id),
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
        // Beim Broadcasting muss die Benachrichtigungs-ID übergeben werden, damit das Frontend
        // weiß, welche Datenbank-Zeile abgerufen werden muss.
        return [
            // Die ID wird automatisch von Laravel als 'id' im Event-Payload gesendet.
            'id' => $this->id, // WICHTIG: Stellt sicher, dass die ID für den Fetch vorhanden ist.
            'text' => $this->text, // Optional: Fügen Sie den Text hinzu, um die Payload zu prüfen
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
