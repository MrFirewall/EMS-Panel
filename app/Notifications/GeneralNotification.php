<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\PrivateChannel;
// ShouldDispatchAfterCommit entfernt, um Transaktions-Konflikte zu vermeiden
use Illuminate\Contracts\Broadcasting\ShouldBroadcast; // Wird für die broadcastOn Methode benötigt!


class GeneralNotification extends Notification implements ShouldBroadcast
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

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable): array
    {
        // Der Broadcast-Kanal muss enthalten sein
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
     * Definiert die Daten, die in der Datenbank und als Broadcast gesendet werden.
     *
     * In Laravel Notifications kann toArray() als Fallback für toBroadcast() dienen,
     * wenn toBroadcast() nicht definiert ist. Wir nutzen toArray() für beide.
     *
     * @param mixed $notifiable
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [
            'text' => $this->text,
            'icon' => $this->icon,
            'url'  => $this->url,
            // HINWEIS: Bei toBroadcast() wird die UUID der Benachrichtigung automatisch hinzugefügt.
        ];
    }
}
