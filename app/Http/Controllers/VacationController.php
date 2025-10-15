<?php

namespace App\Http\Controllers;

use App\Models\Vacation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ActivityLog;

class VacationController extends Controller
{
    /**
     * Schützt die Methoden des Controllers mit den entsprechenden Berechtigungen.
     */
    public function __construct()
    {
        // Aktionen, die jeder Mitarbeiter mit der Berechtigung ausführen kann
        $this->middleware('can:vacations.create')->only(['create', 'store']);
        
        // Aktionen, die nur Administratoren mit der Berechtigung ausführen können
        $this->middleware('can:vacations.manage')->only(['index', 'updateStatus']);
    }

    /**
     * Zeigt das Formular an, um einen neuen Urlaubsantrag zu erstellen.
     * (Mitarbeiter-Ansicht)
     */
    public function create()
    {
        return view('vacations.create');
    }

    /**
     * Speichert einen neuen Urlaubsantrag.
     * (Mitarbeiter-Ansicht)
     */
    public function store(Request $request)
    {
        $request->validate([
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $vacation = Vacation::create([
            'user_id' => Auth::id(),
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        // Logging
        ActivityLog::create([
            'user_id' => Auth::id(),
            'log_type' => 'VACATION',
            'action' => 'REQUESTED',
            'target_id' => $vacation->id,
            'description' => "Urlaubsantrag ({$vacation->id}) für {$vacation->start_date} bis {$vacation->end_date} gestellt.",
        ]);

        return redirect()->route('dashboard')->with('success', 'Urlaubsantrag erfolgreich eingereicht und zur Prüfung vorgemerkt.');
    }

    /**
     * Zeigt die Liste aller Urlaubsanträge zur Verwaltung an.
     * (Admin-Ansicht)
     */
    public function index()
    {
        $vacations = Vacation::with(['user', 'approver'])->latest()->paginate(10);
        return view('admin.vacations.index', compact('vacations'));
    }

    /**
     * Aktualisiert den Status eines Urlaubsantrags (genehmigen/ablehnen).
     * (Admin-Ansicht)
     */
    public function updateStatus(Request $request, Vacation $vacation)
    {
        $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'internal_notes' => ['nullable', 'string', 'max:500'],
        ]);
        
        $newStatus = $request->status;

        // Aktualisiere den Status und den Bearbeiter
        $vacation->update([
            'status' => $newStatus,
            'approved_by' => Auth::id(),
            'internal_notes' => $request->internal_notes,
        ]);
        
        $user = $vacation->user;
        
        if ($user) {
            // Metadaten im Benutzerprofil aktualisieren
            $user->last_edited_at = now(); 
            $user->last_edited_by = Auth::user()->name; 
            $user->save();
        }

        // Logging
        $action = ($newStatus === 'approved') ? 'APPROVED' : 'REJECTED';
        $statusText = ($newStatus === 'approved') ? 'genehmigt' : 'abgelehnt';
        $description = "Urlaubsantrag ({$vacation->id}) von '{$user->name}' wurde {$statusText}.";

        ActivityLog::create([
            'user_id' => Auth::id(),
            'log_type' => 'VACATION',
            'action' => $action,
            'target_id' => $vacation->id,
            'description' => $description,
        ]);

        $message = ($newStatus === 'approved') 
            ? 'Urlaubsantrag erfolgreich genehmigt.' 
            : 'Urlaubsantrag erfolgreich abgelehnt.';

        return back()->with('success', $message);
    }
}