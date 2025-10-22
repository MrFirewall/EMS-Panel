<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class NotificationController extends Controller
{
    /**
     * Ruft ungelesene Benachrichtigungen für das Dropdown ab, gruppiert diese
     * nach Typ (Icon) und gibt die hierarchische Struktur zurück.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function fetch()
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $user = Auth::user();

        // Hole alle ungelesenen Benachrichtigungen und sortiere sie nach Erstellung (neueste zuerst)
        $notifications = $user->unreadNotifications->sortByDesc('created_at');

        // Funktion zur Erzeugung eines spezifischen Gruppentextes
        $getGroupText = function ($icon, $count) {
            $prefix = ($count > 1) ? "{$count} neue " : "Eine neue ";
            
            switch ($icon) {
                case 'fas fa-file-alt':
                    return $prefix . (($count > 1) ? 'Einträge in Personalakten' : 'Aktenergänzung');
                case 'fas fa-user-plus':
                    return $prefix . (($count > 1) ? 'Mitarbeiteranmeldungen' : 'Mitarbeiteranmeldung');
                case 'fas fa-exclamation-triangle':
                    return $prefix . (($count > 1) ? 'Warnungen oder Fehler' : 'Warnmeldung');
                case 'fas fa-comment':
                    return $prefix . (($count > 1) ? 'Kommentare/Nachrichten' : 'Nachricht');
                case 'fas fa-clipboard-list':
                    return $prefix . (($count > 1) ? 'Aufgaben/Checklisten' : 'Aufgabe');
                case 'fas fa-sign-out-alt':
                    return $prefix . (($count > 1) ? 'Austritte/Kündigungen' : 'Austrittsmeldung');
                case 'fas fa-birthday-cake':
                    return $prefix . (($count > 1) ? 'Geburtstage' : 'Geburtstag');
                case 'fas fa-check-circle':
                    return $prefix . (($count > 1) ? 'Bestätigungen' : 'Bestätigung');
                default:
                    return $prefix . (($count > 1) ? 'Meldungen' : 'Meldung');
            }
        };

        // Gruppierung der Benachrichtigungen nach Icon-Typ
        $groupedNotifications = $notifications
            ->groupBy(function($notification) {
                // Verwenden Sie den Icon-Namen als Gruppierungsschlüssel.
                return $notification->data['icon'] ?? 'fas fa-bell';
            })
            ->map(function($group, $icon) use ($getGroupText) {
                $count = $group->count();
                
                // Gruppentitel generieren (z.B. "3 neue Aufgaben")
                $groupTitle = $getGroupText($icon, $count);
                
                // Map die individuellen Benachrichtigungen innerhalb der Gruppe
                $individualItems = $group->map(function($notification) {
                    return [
                        'id'    => $notification->id,
                        'text'  => $notification->data['text'] ?? 'Unbekannte Benachrichtigung',
                        'url'   => $notification->data['url'] ?? '#',
                        'time'  => $notification->created_at->diffForHumans(null, true, true),
                    ];
                })->values();

                return [
                    'group_title' => $groupTitle,
                    'group_icon'  => $icon,
                    'group_count' => $count,
                    'items'       => $individualItems, // Die Liste der einzelnen Benachrichtigungen
                ];
            })
            ->values();

        // Rendere das Partial-View mit der hierarchischen Struktur
        $html = view('layouts._notifications', ['groupedNotifications' => $groupedNotifications, 'totalCount' => $notifications->count()])->render();

        return response()->json([
            'count'      => $notifications->count(), 
            'items_html' => $html
        ]);
    }
    
    // ... (index, markAllRead, markAsRead und destroy bleiben unverändert)

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
