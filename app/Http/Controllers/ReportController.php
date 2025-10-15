<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ActivityLog;

class ReportController extends Controller
{
    /**
     * Verknüpft den Controller mit der ReportPolicy.
     *
     * Laravel prüft nun automatisch vor jeder Aktion, ob der Benutzer die
     * entsprechende Berechtigung aus der Policy-Klasse hat.
     * index() -> viewAny(), create() -> create(), edit() -> update() etc.
     */
    public function __construct()
    {
        $this->authorizeResource(Report::class, 'report');
    }

    /**
     * Zeigt eine Liste aller Einsatzberichte an.
     * Die Berechtigung wird durch die 'viewAny'-Methode in der ReportPolicy geprüft.
     */
    public function index()
    {
        $reports = Report::with('user')->latest()->paginate(15);
        return view('reports.index', compact('reports'));
    }

    /**
     * Zeigt das Formular zum Erstellen eines neuen Berichts an.
     * Die Berechtigung wird durch die 'create'-Methode in der ReportPolicy geprüft.
     */
    public function create()
    {
        return view('reports.create');
    }

    /**
     * Speichert einen neuen Bericht in der Datenbank.
     * Die Berechtigung wird durch die 'create'-Methode in der ReportPolicy geprüft.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'patient_name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'incident_description' => 'required|string',
            'actions_taken' => 'required|string',
        ]);

        $validatedData['user_id'] = Auth::id();
        $report = Report::create($validatedData);

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
     * Die Berechtigung wird durch die 'view'-Methode in der ReportPolicy geprüft.
     */
    public function show(Report $report)
    {
        return view('reports.show', compact('report'));
    }

    /**
     * Zeigt das Formular zum Bearbeiten eines Berichts an.
     * Die Berechtigung wird durch die 'update'-Methode in der ReportPolicy geprüft.
     */
    public function edit(Report $report)
    {
        return view('reports.edit', compact('report'));
    }

    /**
     * Aktualisiert einen Bericht in der Datenbank.
     * Die Berechtigung wird durch die 'update'-Methode in der ReportPolicy geprüft.
     */
    public function update(Request $request, Report $report)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'patient_name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'incident_description' => 'required|string',
            'actions_taken' => 'required|string',
        ]);

        $report->update($validatedData);
        
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
     * Die Berechtigung wird durch die 'delete'-Methode in der ReportPolicy geprüft.
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
            'target_id' => $reportId,
            'description' => "Einsatzbericht '{$reportTitle}' ({$reportId}) gelöscht.",
        ]);

        return redirect()->route('reports.index')->with('success', 'Einsatzbericht gelöscht!');
    }
}