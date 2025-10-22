<?php

// KORRIGIERT: Namespace an die Admin-Struktur angepasst
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ActivityLog;
use App\Events\PotentiallyNotifiableActionOccurred; // Event hinzufügen

class AnnouncementController extends Controller
{
    public function __construct()
    {
        // KORRIGIERT: 'announcements.list' zu 'announcements.view' geändert
        $this->middleware('can:announcements.view')->only('index');
        $this->middleware('can:announcements.create')->only(['create', 'store']);
        $this->middleware('can:announcements.edit')->only(['edit', 'update']);
        $this->middleware('can:announcements.delete')->only('destroy');
    }

    public function index()
    {
        $announcements = Announcement::with('user')->latest()->get();
        return view('admin.announcements.index', compact('announcements'));
    }

    public function create()
    {
        return view('admin.announcements.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $data['user_id'] = Auth::id();
        $data['is_active'] = $request->boolean('is_active'); // boolean() verwenden
        $announcement = Announcement::create($data);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'log_type' => 'ANNOUNCEMENT',
            'action' => 'CREATED',
            'target_id' => $announcement->id,
            'description' => "Neue Ankündigung '{$announcement->title}' erstellt.",
        ]);

        // --- BENACHRICHTIGUNG VIA EVENT ---
        PotentiallyNotifiableActionOccurred::dispatch(
            'AnnouncementController@store',
            Auth::user(), // Der Ersteller
            $announcement, // Die erstellte Ankündigung
            Auth::user() // Der Akteur
        );
        // ---------------------------------

        return redirect()->route('admin.announcements.index'); // Ohne success
    }

    public function edit(Announcement $announcement)
    {
        return view('admin.announcements.edit', compact('announcement'));
    }

    public function update(Request $request, Announcement $announcement)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $oldStatus = $announcement->is_active ? 'aktiv' : 'inaktiv';
        $newStatusBool = $request->boolean('is_active'); // boolean() verwenden
        $newStatus = $newStatusBool ? 'aktiv' : 'inaktiv';
        $data['is_active'] = $newStatusBool;
        $announcement->update($data);

        $description = "Ankündigung '{$announcement->title}' ({$announcement->id}) aktualisiert.";
        if ($oldStatus !== $newStatus) {
             $description .= " Status geändert von {$oldStatus} zu {$newStatus}.";
        }
        
        ActivityLog::create([
            'user_id' => Auth::id(),
            'log_type' => 'ANNOUNCEMENT',
            'action' => 'UPDATED',
            'target_id' => $announcement->id,
            'description' => $description,
        ]);

        // --- BENACHRICHTIGUNG VIA EVENT ---
        PotentiallyNotifiableActionOccurred::dispatch(
            'AnnouncementController@update',
            Auth::user(), // Der Bearbeiter
            $announcement, // Die aktualisierte Ankündigung
            Auth::user() // Der Akteur
        );
        // ---------------------------------

        return redirect()->route('admin.announcements.index'); // Ohne success
    }

    public function destroy(Announcement $announcement)
    {
        $announcementTitle = $announcement->title;
        $announcementId = $announcement->id;
        
        // Temporäre Kopie der Daten für das Event erstellen, da das Model gelöscht wird
        $deletedAnnouncementData = $announcement->toArray(); 
        
        $announcement->delete();

        ActivityLog::create([
            'user_id' => Auth::id(),
            'log_type' => 'ANNOUNCEMENT',
            'action' => 'DELETED',
            'target_id' => $announcementId,
            'description' => "Ankündigung '{$announcementTitle}' ({$announcementId}) gelöscht.",
        ]);

        // --- BENACHRICHTIGUNG VIA EVENT ---
        // Hinweis: Wir können das gelöschte Model nicht direkt übergeben.
        // Wir übergeben den Akteur und ggf. Daten aus der Kopie im Listener auswerten.
        PotentiallyNotifiableActionOccurred::dispatch(
            'AnnouncementController@destroy',
            Auth::user(), // Der Löschende
            (object) $deletedAnnouncementData, // Übergabe als Objekt, um Typkonsistenz zu wahren (könnte auch null sein)
            Auth::user() // Der Akteur
        );
        // ---------------------------------

        return redirect()->route('admin.announcements.index'); // Ohne success
    }
}
