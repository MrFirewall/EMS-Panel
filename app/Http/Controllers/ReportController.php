<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\User; // Hinzugefügt für die Suche
use App\Models\Citizen; // ANNAHME: Du hast ein Citizen-Model
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ActivityLog;

class ReportController extends Controller
{
    /**
     * Verknüpft den Controller mit der ReportPolicy.
     */
    public function __construct()
    {
        $this->authorizeResource(Report::class, 'report');
    }

    /**
     * Zeigt eine Liste aller Einsatzberichte an, inkl. Suchfunktion.
     */
    public function index(Request $request)
    {
        $query = Report::with('user')->latest();

        // Nur Admins dürfen alle Berichte sehen, andere nur ihre eigenen
        if (Auth::user()->cannot('viewAny', Report::class)) {
            $query->where('user_id', Auth::id());
        }

        // Suchfunktion
        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                  ->orWhere('patient_name', 'like', "%{$searchTerm}%")
                  ->orWhereHas('user', function ($userQuery) use ($searchTerm) {
                      $userQuery->where('name', 'like', "%{$searchTerm}%");
                  });
            });
        }

        $reports = $query->paginate(15)->withQueryString();

        return view('reports.index', compact('reports'));
    }

    /**
     * Zeigt das Formular zum Erstellen eines neuen Berichts an.
     */
    public function create()
    {
        $templates = config('report_templates', []);
        $citizens = Citizen::orderBy('name')->get(); // Bürgerliste laden
        $allStaff = User::orderBy('name')->get(); // Alle Mitarbeiter laden

        return view('reports.create', compact('templates', 'citizens', 'allStaff'));
    }

    /**
     * Speichert einen neuen Bericht in der Datenbank.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'patient_name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'incident_description' => 'required|string',
            'actions_taken' => 'required|string',
            'attending_staff' => 'nullable|array',
            'attending_staff.*' => 'exists:users,id',
        ]);

        $validatedData['user_id'] = Auth::id();
        $report = Report::create($validatedData);
        
        if ($request->has('attending_staff')) {
            $report->attendingStaff()->attach($request->input('attending_staff'));
        }
        // Logging
        ActivityLog::create([
            'user_id' => Auth::id(),
            'log_type' => 'REPORT',
            'action' => 'CREATED',
            'target_id' => $report->id,
            'description' => "Einsatzbericht '{$report->title}' erstellt (Patient: {$report->patient_name}).",
        ]);

        return redirect()->route('reports.index')->with('success', 'Einsatzbericht erfolgreich erstellt!');
    }

    /**
     * Zeigt einen einzelnen Bericht detailliert an.
     */
    public function show(Report $report)
    {
        return view('reports.show', compact('report'));
    }

    /**
     * Zeigt das Formular zum Bearbeiten eines Berichts an.
     */
    public function edit(Report $report)
    {
        $templates = config('report_templates', []);
        $citizens = Citizen::orderBy('name')->get(); // Bürgerliste laden
        $allStaff = User::orderBy('name')->get(); // Alle Mitarbeiter laden
        $report->load('attendingStaff'); // Lade die zugehörigen Mitarbeiter

        return view('reports.edit', compact('report', 'templates', 'citizens', 'allStaff'));
    }

    /**
     * Aktualisiert einen Bericht in der Datenbank.
     */
    public function update(Request $request, Report $report)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'patient_name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'incident_description' => 'required|string',
            'actions_taken' => 'required|string',
            'attending_staff' => 'nullable|array',
            'attending_staff.*' => 'exists:users,id',
        ]);

        $report->update($validatedData);
        $report->attendingStaff()->sync($request->input('attending_staff', []));
        
        // Logging
        ActivityLog::create([
            'user_id' => Auth::id(),
            'log_type' => 'REPORT',
            'action' => 'UPDATED',
            'target_id' => $report->id,
            'description' => "Einsatzbericht '{$report->title}' ({$report->id}) aktualisiert.",
        ]);

        return redirect()->route('reports.index')->with('success', 'Einsatzbericht erfolgreich aktualisiert!');
    }

    /**
     * Löscht einen Bericht aus der Datenbank.
     */
    public function destroy(Report $report)
    {
        $reportTitle = $report->title;
        $reportId = $report->id;

        $report->delete();
        
        // Logging
        ActivityLog::create([
            'user_id' => Auth::id(),
            'log_type' => 'REPORT',
            'action' => 'DELETED',
            'target_id' => $reportId, // Use the stored ID after deletion
            'description' => "Einsatzbericht '{$reportTitle}' ({$reportId}) gelöscht.",
        ]);

        return redirect()->route('reports.index')->with('success', 'Einsatzbericht gelöscht!');
    }
}

