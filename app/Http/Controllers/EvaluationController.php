<?php

namespace App\Http\Controllers;

use App\Models\Evaluation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
// Fügen Sie ggf. das ActivityLog-Modell hinzu, falls es noch nicht importiert ist
use App\Models\ActivityLog;

class EvaluationController extends Controller
{
    public static array $grades = ['Sehr Gut', 'Gut', 'Befriedigend', 'Ausreichend', 'Mangelhaft', 'Ungenügend', 'Nicht feststellbar'];
    public static array $periods = ['00 - 06 Uhr', '06 - 12 Uhr', '12 - 18 Uhr', '18 - 00 Uhr'];
    public static array $typeLabels = ['azubi', 'praktikant', 'mitarbeiter', 'leitstelle', 'gutachten', 'anmeldung'];

    public function __construct()
    {
        // Schützt alle Methoden, die ein Formular zum Erstellen anzeigen oder eine Bewertung speichern
        $this->middleware('can:evaluations.create')->only(['azubi', 'praktikant', 'leitstelle', 'mitarbeiter', 'store']);
    }

    public function index()
    {
        // KORRIGIERT: Prüft auf die neue, spezifische Berechtigung
        $canViewAll = Auth::user()->can('evaluations.view.all');

        if ($canViewAll) {
            // Admin-Ansicht: Lade alle Bewertungen
            $evaluations = Evaluation::with(['user', 'evaluator'])->latest()->paginate(20);
        } else {
            // Mitarbeiter-Ansicht: Lade nur die erhaltenen Bewertungen (an user_id)
            // Stellt sicher, dass der User seine eigenen Bewertungen sehen darf
            abort_if(Auth::user()->cannot('evaluations.view.own'), 403);
            
            $evaluations = Evaluation::where('user_id', Auth::id())
                                      ->with('evaluator')
                                      ->latest()
                                      ->paginate(20);
        }
        
        $counts = $this->getEvaluationCounts();

        // KORRIGIERT: Gibt die Variable mit aussagekräftigerem Namen an die View
        return view('forms.evaluations.index', compact('evaluations', 'counts', 'canViewAll'));
    }
    
    /**
     * Hilfsmethode zur Ermittlung der Zähler für die Index-Übersicht.
     */
    private function getEvaluationCounts()
    {
        $currentUserId = Auth::id();
        $counts = ['verfasst' => [], 'erhalten' => [], 'gesamt' => []];

        foreach (self::$typeLabels as $type) {
            $counts['verfasst'][$type] = 0;
            $counts['erhalten'][$type] = 0;
            $counts['gesamt'][$type] = 0;
        }

        $allEvaluations = Evaluation::all();

        foreach ($allEvaluations as $evaluation) {
            $type = $evaluation->evaluation_type;

            if (!isset($counts['gesamt'][$type])) continue; 
            
            $counts['gesamt'][$type]++;
            
            if ($evaluation->evaluator_id === $currentUserId) {
                $counts['verfasst'][$type]++;
            }

            if ($evaluation->user_id === $currentUserId) {
                $counts['erhalten'][$type]++;
            }
        }
        return $counts;
    }
    
    // =========================================================================
    // FORMULAR ANSICHTEN (bleiben unverändert)
    // =========================================================================

    public function azubi()
    {
        $users = User::role('emt-trainee')->orderBy('name')->get();
        return view('forms.evaluations.azubi', ['users' => $users, 'evaluationType' => 'azubi']);
    }

    public function praktikant()
    {
        return view('forms.evaluations.praktikant', ['users' => collect(), 'evaluationType' => 'praktikant']);
    }

    public function leitstelle()
    {
        $users = User::orderBy('name')->get();
        return view('forms.evaluations.leitstelle', ['users' => $users, 'evaluationType' => 'leitstelle']);
    }

    public function mitarbeiter()
    {
        $exemptRoles = ['emt-trainee', 'praktikant'];
        $users = User::whereDoesntHave('roles', function ($query) use ($exemptRoles) {
            $query->whereIn('name', $exemptRoles);
        })->orderBy('name')->get();
        return view('forms.evaluations.mitarbeiter', ['users' => $users, 'evaluationType' => 'mitarbeiter']);
    }
    
    // =========================================================================
    // DATEN SPEICHERN & DETAILANSICHT
    // =========================================================================

    /**
     * Speichert die Bewertung (unabhängig vom Typ).
     */
    public function store(Request $request)
    {
        $evaluationType = $request->input('evaluation_type');
        
        $validationRules = [
            'evaluation_type' => 'required|in:' . implode(',', self::$typeLabels),
            'evaluation_date' => 'required|date', // NEU: Validierung für das Datum
            'period' => 'required|string',
            'description' => 'nullable|string',
            'data' => 'required|array', 
            'data.*' => 'required|string',
        ];

        if ($evaluationType === 'praktikant') {
            $validationRules['target_name'] = 'required|string|max:255';
        } else {
            $validationRules['user_id'] = 'required|exists:users,id';
        }
        
        $validated = $request->validate($validationRules);
        
        // Logik zur Bestimmung des Ziels (ID oder Name)
        if ($evaluationType === 'praktikant') {
             $targetUserId = null; 
             $targetName = $validated['target_name'];
        } else {
             $targetUserId = $validated['user_id'];
             $targetName = User::find($targetUserId)->name ?? $validated['user_id'];
        }

        $evaluation = Evaluation::create([
            'user_id' => $targetUserId,
            'target_name' => $targetName,
            'evaluator_id' => Auth::id(),
            'evaluation_type' => $validated['evaluation_type'],
            'evaluation_date' => $validated['evaluation_date'], // NEU: Datum speichern
            'period' => $validated['period'],
            'json_data' => $validated['data'],
            'description' => $validated['description'],
        ]);
        
        // Logging hinzufügen (optional)
        ActivityLog::create([
             'user_id' => Auth::id(),
             'log_type' => 'EVALUATION',
             'action' => 'CREATED',
             'target_id' => $evaluation->id,
             'description' => "Neue Bewertung für {$targetName} ({$validated['evaluation_type']}) erstellt.",
          ]);

        return redirect()->route('forms.evaluations.index')->with('success', 'Bewertung erfolgreich gespeichert!');
    }

    /**
     * Zeigt die Details einer einzelnen Bewertung an.
     * @param \App\Models\Evaluation $evaluation
     * @return \Illuminate\View\View
     */
    public function show(Evaluation $evaluation)
    {
        $evaluation->load(['user', 'evaluator']);

        // KORRIGIERT: Die Berechtigungsprüfung nutzt nun die granularen Rechte
        $canSee = Auth::user()->can('evaluations.view.all') // Admin darf alles sehen
                  || Auth::id() === $evaluation->evaluator_id     // Der Ersteller darf sehen
                  || (Auth::id() === $evaluation->user_id && Auth::user()->can('evaluations.view.own')); // Der Empfänger darf sehen, wenn er die Berechtigung hat

        if (!$canSee) {
            abort(403, 'Sie sind nicht berechtigt, diese Bewertung einzusehen.');
        }

        $evaluationData = is_array($evaluation->json_data) 
            ? $evaluation->json_data 
            : json_decode($evaluation->json_data, true);

        $targetName = $evaluation->target_name ?? $evaluation->user?->name ?? 'Unbekannt';

        return view('forms.evaluations.show', compact('evaluation', 'evaluationData', 'targetName'));
    }
}