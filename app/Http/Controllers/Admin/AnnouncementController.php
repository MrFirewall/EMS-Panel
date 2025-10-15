<?php

// KORRIGIERT: Namespace an die Admin-Struktur angepasst
namespace App\Http\Controllers\Admin; 

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ActivityLog;

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

    // Der Rest deines Controllers (index, create, store, etc.) bleibt exakt gleich,
    // da deine Logik für Validierung und Logging bereits einwandfrei ist.
    
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
        $data['is_active'] = $request->has('is_active');
        $announcement = Announcement::create($data);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'log_type' => 'ANNOUNCEMENT',
            'action' => 'CREATED',
            'target_id' => $announcement->id,
            'description' => "Neue Ankündigung '{$announcement->title}' erstellt.",
        ]);

        return redirect()->route('admin.announcements.index')->with('success', 'Ankündigung erstellt.');
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
        $newStatus = $request->has('is_active') ? 'aktiv' : 'inaktiv';
        $data['is_active'] = $request->has('is_active');
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

        return redirect()->route('admin.announcements.index')->with('success', 'Ankündigung aktualisiert.');
    }

    public function destroy(Announcement $announcement)
    {
        $announcementTitle = $announcement->title;
        $announcementId = $announcement->id;
        $announcement->delete();

        ActivityLog::create([
            'user_id' => Auth::id(),
            'log_type' => 'ANNOUNCEMENT',
            'action' => 'DELETED',
            'target_id' => $announcementId,
            'description' => "Ankündigung '{$announcementTitle}' ({$announcementId}) gelöscht.",
        ]);

        return redirect()->route('admin.announcements.index')->with('success', 'Ankündigung gelöscht.');
    }
}