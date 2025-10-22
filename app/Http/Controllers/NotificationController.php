<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon; // Wichtig für die Zeitangabe

class NotificationController extends Controller
{
    /**
     * Ruft ungelesene Benachrichtigungen für das Dropdown ab.
     */
    public function fetch()
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $user = Auth::user();
        
        // Hole alle ungelesenen Benachrichtigungen
        $notifications = $user->unreadNotifications;

        // Formatiere die Daten
        $items = $notifications->map(function($notification) {
            return [
                'text' => $notification->data['text'] ?? 'Unbekannte Benachrichtigung',
                'icon' => $notification->data['icon'] ?? 'fas fa-bell',
                'url'  => $notification->data['url'] ?? '#',
                'time' => $notification->created_at->diffForHumans(null, true, true), // z.B. "5m"
            ];
        });

        // Rendere das Partial-View
        $html = view('layouts._notifications', ['notifications' => $items])->render();

        // WICHTIG: Wir markieren sie hier NICHT mehr als gelesen.
        // Das passiert jetzt manuell per Klick.
        // $user->unreadNotifications->markAsRead(); // DIESE ZEILE WURDE ENTFERNT

        return response()->json([
            'count'      => $items->count(),
            'items_html' => $html
        ]);
    }

    /**
     * Zeigt die Archiv-Seite mit allen Benachrichtigungen (gelesen und ungelesen).
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
     */
    public function markAllRead()
    {
        Auth::user()->unreadNotifications->markAsRead();

        return redirect()->back()->with('success', 'Alle Benachrichtigungen wurden als gelesen markiert.');
    }

    /**
     * Löscht eine einzelne Benachrichtigung.
     */
    public function destroy($id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->delete();

        return redirect()->back()->with('success', 'Benachrichtigung gelöscht.');
    }
}

