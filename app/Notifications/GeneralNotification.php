<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GeneralNotification extends Notification
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
        // Wir speichern dies nur in der Datenbank
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        // Diese Daten werden in der 'data'-Spalte der 'notifications'-Tabelle gespeichert.
        // Unser NotificationController wird diese Daten lesen.
        return [
            'text' => $this->text,
            'icon' => $this->icon,
            'url'  => $this->url,
        ];
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
