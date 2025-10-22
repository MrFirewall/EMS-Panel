<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class NotificationController extends Controller
{
    /**
     * Ruft ungelesene Benachrichtigungen für das Dropdown ab und gruppiert diese nach Typ (Icon).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function fetch()
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $user = Auth::user();

        // Hole alle ungelesenen Benachrichtigungen
        $notifications = $user->unreadNotifications;

        // Gruppierung der Benachrichtigungen nach Icon-Typ
        $groupedItems = $notifications
            ->groupBy(function($notification) {
                // Verwenden Sie den Icon-Namen als Gruppierungsschlüssel.
                return $notification->data['icon'] ?? 'fas fa-bell';
            })
            ->map(function($group, $icon) {
                $count = $group->count();
                $first = $group->first(); // Verwende die erste Benachrichtigung für URL/ID/Zeit
                
                // Formatiere den Text basierend auf der Gruppengröße
                // Wenn mehr als eine Benachrichtigung im Icon-Typ, fasse zusammen.
                $text = $count > 1 
                    ? "{$count} neue Meldungen dieses Typs"
                    : ($first->data['text'] ?? 'Unbekannte Benachrichtigung');

                return [
                    // Wichtig: ID der ersten Benachrichtigung für den Markierungsklick (markAsRead)
                    'id'    => $first->id, 
                    'text'  => $text,
                    'icon'  => $icon,
                    'url'   => $first->data['url'] ?? '#', 
                    // Zeige die Zeit der neuesten Benachrichtigung im Dropdown
                    'time'  => $first->created_at->diffForHumans(null, true, true),
                    'count' => $count, // Anzahl der Elemente in dieser Gruppe
                ];
            })
            ->values(); // Setze die Schlüssel zurück (optional, aber sauber)

        // Rendere das Partial-View mit den gruppierten Elementen
        $html = view('layouts._notifications', ['notifications' => $groupedItems])->render();

        return response()->json([
            // Wir geben die Gesamtzahl der ungelesenen Benachrichtigungen (nicht der Gruppen) zurück
            'count'      => $notifications->count(), 
            'items_html' => $html
        ]);
    }

    /**
     * Zeigt die Archiv-Seite mit allen Benachrichtigungen (gelesen und ungelesen).
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = Auth::user();

        // Hole alle Benachrichtigungen, paginiert
        $allNotifications = $user->notifications()->paginate(20);

        // Hole nur die ungelesenen für den Zähler
        $unreadCount = $user->unreadNotifications()->count();

        return view('notifications.index', [
            'allNotifications' => $allNotifications,
            'unreadCount' => $unreadCount
        ]);
    }

    /**
     * Markiert alle ungelesenen Benachrichtigungen als gelesen.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function markAllRead()
    {
        Auth::user()->unreadNotifications->markAsRead();

        return redirect()->back()->with('success', 'Alle Benachrichtigungen wurden als gelesen markiert.');
    }

    /**
     * Markiert eine einzelne ungelesene Benachrichtigung als gelesen und leitet ggf. zur Ziel-URL weiter.
     *
     * @param string $id Die ID der zu markierenden Benachrichtigung.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function markAsRead($id)
    {
        // Finde die Benachrichtigung über die ID und stelle sicher, dass sie dem eingeloggten Benutzer gehört
        $notification = Auth::user()->notifications()->where('id', $id)->first();

        if ($notification && $notification->unread()) {
            $notification->markAsRead();
            
            // Wenn eine URL übergeben wurde, leite dorthin weiter
            if (isset($notification->data['url']) && $notification->data['url'] !== '#') {
                return redirect($notification->data['url']);
            }
        }
        
        // Wenn keine URL vorhanden oder Benachrichtigung nicht gefunden/gelesen, leite zurück
        return redirect()->back(); 
    }

    /**
     * Löscht eine einzelne Benachrichtigung.
     *
     * @param string $id Die ID der zu löschenden Benachrichtigung.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->delete();

        return redirect()->back()->with('success', 'Benachrichtigung gelöscht.');
    }
}