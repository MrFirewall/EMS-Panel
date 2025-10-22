<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon; // Wichtig für die Zeitangabe

class NotificationController extends Controller
{
    /**
     * Ruft alle ungelesenen Benachrichtigungen für den eingeloggten Benutzer ab
     * und rendert sie als HTML für das Dropdown-Menü.
     */
    public function fetch()
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $user = Auth::user();
        
        // Hole alle ungelesenen Benachrichtigungen aus der Datenbank
        $notifications = $user->unreadNotifications;

        // Formatiere die Daten so, wie es das _notifications.blade.php Partial erwartet
        $items = $notifications->map(function($notification) {
            return [
                'text' => $notification->data['text'] ?? 'Unbekannte Benachrichtigung',
                'icon' => $notification->data['icon'] ?? 'fas fa-bell',
                'url'  => $notification->data['url'] ?? '#',
                'time' => $notification->created_at->diffForHumans(null, true, true), // z.B. "vor 5m" oder "5m"
            ];
        });

        // Rendere das Partial-View mit den formatierten Daten
        // (Wir verwenden das Partial, das Sie bereits haben)
        $html = view('layouts._notifications', ['notifications' => $items])->render();

        // Markiere die Benachrichtigungen als gelesen, *nachdem* wir sie abgerufen haben
        $user->unreadNotifications->markAsRead();

        // Sende die Anzahl und das gerenderte HTML zurück
        return response()->json([
            'count'      => $items->count(),
            'items_html' => $html
        ]);
    }
}
