<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class NotificationController extends Controller
{
    /**
     * Fetches unread notifications for the dropdown, groups them by type (icon),
     * and returns the hierarchical structure.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function fetch()
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $user = Auth::user();

        // Get all unread notifications and sort them by creation date (newest first)
        $notifications = $user->unreadNotifications->sortByDesc('created_at');

        // Function to generate specific group text
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

        // Group notifications by icon type
        $groupedNotifications = $notifications
            ->groupBy(function($notification) {
                // Use the icon name as the grouping key.
                return $notification->data['icon'] ?? 'fas fa-bell';
            })
            ->map(function($group, $icon) use ($getGroupText) {
                $count = $group->count();
                
                // Generate group title (e.g., "3 neue Aufgaben")
                $groupTitle = $getGroupText($icon, $count);
                
                // Map individual notifications within the group
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
                    'items'       => $individualItems, // List of individual notifications
                ];
            })
            ->values();

        // Render the partial view with the hierarchical structure
        $html = view('layouts._notifications', ['groupedNotifications' => $groupedNotifications, 'totalCount' => $notifications->count()])->render();

        return response()->json([
            'count'      => $notifications->count(), 
            'items_html' => $html
        ]);
    }
    
    /**
     * Displays the archive page with all notifications (read and unread).
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = Auth::user();
        // Neueste zuerst sortiert.
        $allNotifications = $user->notifications()
                                 ->orderBy('created_at', 'desc')
                                 ->get();

        // Get only unread ones for the counter
        $unreadCount = $user->unreadNotifications()->count();

        return view('notifications.index', [
            'allNotifications' => $allNotifications,
            'unreadCount' => $unreadCount
        ]);
    }

    /**
     * Marks all unread notifications as read.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function markAllRead()
    {
        Auth::user()->unreadNotifications->markAsRead();

        // Removed ->with('success', ...)
        return redirect()->back(); 
    }

    /**
     * Marks a single unread notification as read and optionally redirects to the target URL.
     *
     * @param string $id The ID of the notification to mark.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function markAsRead($id)
    {
        // Find the notification by ID and ensure it belongs to the logged-in user
        $notification = Auth::user()->notifications()->where('id', $id)->first();

        if ($notification && $notification->unread()) {
            $notification->markAsRead();
            
            // If a URL was provided, redirect there
            if (isset($notification->data['url']) && $notification->data['url'] !== '#') {
                return redirect($notification->data['url']);
            }
        }
        
        // If no URL or notification not found/read, redirect back
        return redirect()->back(); 
    }

    /**
     * Deletes a single notification.
     *
     * @param string $id The ID of the notification to delete.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->delete();

        // Removed ->with('success', ...)
        return redirect()->back(); 
    }

    /**
     * Markiert ausgewählte Benachrichtigungen als gelesen.
     */
    public function bulkMarkRead(Request $request)
    {
        $request->validate([
            'notification_ids'   => 'required|array',
            'notification_ids.*' => 'string|exists:notifications,id',
        ]);

        Auth::user()->notifications()
            ->whereIn('id', $request->notification_ids)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return redirect()->back()->with('success', 'Ausgewählte Benachrichtigungen wurden als gelesen markiert.');
    }

    /**
     * Löscht ausgewählte Benachrichtigungen.
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'notification_ids'   => 'required|array',
            'notification_ids.*' => 'string|exists:notifications,id',
        ]);

        Auth::user()->notifications()
            ->whereIn('id', $request->notification_ids)
            ->delete();

        return redirect()->back()->with('success', 'Ausgewählte Benachrichtigungen wurden gelöscht.');
    }

    /**
     * Löscht alle gelesenen Benachrichtigungen des Benutzers.
     */
    public function clearRead()
    {
        Auth::user()->notifications()
            ->whereNotNull('read_at')
            ->delete();
            
        return redirect()->back()->with('success', 'Alle gelesenen Benachrichtigungen wurden gelöscht.');
    }
}
