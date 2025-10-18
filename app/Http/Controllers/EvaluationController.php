<?php

namespace App\Http\Controllers;

use App\Models\Evaluation;
use App\Models\User;
use App\Models\ActivityLog;
use App\Models\TrainingModule; // Wichtig für die neuen Formulare
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
 
class EvaluationController extends Controller
{
    // Statische Arrays für Konsistenz
    public static array $grades = ['Sehr Gut', 'Gut', 'Befriedigend', 'Ausreichend', 'Mangelhaft', 'Ungenügend', 'Nicht feststellbar'];
    public static array $periods = ['00 - 06 Uhr', '06 - 12 Uhr', '12 - 18 Uhr', '18 - 00 Uhr'];
    
    // NEU: 'modul_anmeldung' & 'pruefung_anmeldung' hinzugefügt
    public static array $typeLabels = [
        'azubi', 'praktikant', 'mitarbeiter', 'leitstelle', 'gutachten', 
        'anmeldung', 'modul_anmeldung', 'pruefung_anmeldung'
    ];

    /**
     * Wendet die EvaluationPolicy auf die Controller-Methoden an.
     */
    public function __construct()
    {
        // Schützt alle Methoden, die ein Formular zum Erstellen anzeigen oder eine Bewertung speichern
        // Bezieht sich auf die 'create' Methode in der EvaluationPolicy
        $this->middleware('can:create,App\Models\Evaluation')->only([
            'azubi', 'praktikant', 'leitstelle', 'mitarbeiter', 'store',
            'modulAnmeldung', 'pruefungsAnmeldung'
        ]);
    }

    /**
     * Zeigt die Übersichtsseite für alle Formulare/Bewertungen an.
     */
    public function index()
    {
        $this->authorize('viewAny', Evaluation::class); // Nutzt die Policy

        $canViewAll = Auth::user()->can('evaluations.view.all'); // Bessere Berechtigungsprüfung

        if ($canViewAll) {
            $evaluations = Evaluation::with(['user', 'evaluator'])->latest()->paginate(20);
        } else {
            // Zeigt alle Formulare an, die der User entweder erhalten oder selbst verfasst hat
            $evaluations = Evaluation::where(function ($query) {
                $query->where('user_id', Auth::id())
                      ->orWhere('evaluator_id', Auth::id());
            })->with(['user', 'evaluator'])->latest()->paginate(20);
        }
        
        $counts = $this->getEvaluationCounts();

        return view('forms.evaluations.index', compact('evaluations', 'counts', 'canViewAll'));
    }
    
    /**
     * Zählt die verschiedenen Formulartypen für die Übersichtsseite.
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
    // FORMULAR-ANSICHTEN FÜR BEWERTUNGEN
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
    // NEU: FORMULAR-ANSICHTEN FÜR AUSBILDUNG
    // =========================================================================
    
    public function modulAnmeldung()
    {
        $existingModuleIds = Auth::user()->trainingModules()->pluck('training_module_id');
        $availableModules = TrainingModule::whereNotIn('id', $existingModuleIds)->orderBy('name')->get();

        return view('forms.evaluations.modul_anmeldung', [
            'evaluationType' => 'modul_anmeldung',
            'modules' => $availableModules
        ]);
    }
    
    public function pruefungsAnmeldung()
    {
        $modulesInTraining = Auth::user()->trainingModules()
            ->wherePivotIn('status', ['angemeldet', 'in_ausbildung'])
            ->orderBy('name')->get();

        return view('forms.evaluations.pruefung_anmeldung', [
            'evaluationType' => 'pruefung_anmeldung',
            'modules' => $modulesInTraining
        ]);
    }
    
    // =========================================================================
    // DATEN SPEICHERN & DETAILANSICHT
    // =========================================================================

    public function store(Request $request)
    {
        $evaluationType = $request->input('evaluation_type');
        
        // Grundlegende Validierungsregeln
        $validationRules = [
            'evaluation_type' => 'required|in:' . implode(',', self::$typeLabels),
            'description' => 'nullable|string',
            'evaluation_date' => 'required|date', 
            'period' => 'required|string',
            'data' => 'nullable|array',
        ];

        // Dynamische Anpassung der Regeln je nach Formulartyp
        if (in_array($evaluationType, ['modul_anmeldung', 'pruefung_anmeldung'])) {
            $validationRules['target_module_id'] = 'required|exists:training_modules,id';
        } elseif ($evaluationType === 'praktikant') {
            $validationRules['target_name'] = 'required|string|max:255';
            $validationRules['data.*'] = 'required|string';
        } else {
            $validationRules['user_id'] = 'required|exists:users,id';
            $validationRules['data.*'] = 'required|string';
        }
        
        $validated = $request->validate($validationRules);
        
        // Daten für die Erstellung vorbereiten
        $data = [
            'evaluator_id' => Auth::id(),
            'evaluation_type' => $validated['evaluation_type'],
            'evaluation_date' => $validated['evaluation_date'],
            'period' => $validated['period'],
            'json_data' => $validated['data'] ?? [],
            'description' => $validated['description'],
        ];

        $logDescription = '';

        // Logik zur Bestimmung des Ziels (ID oder Name)
        if (in_array($evaluationType, ['modul_anmeldung', 'pruefung_anmeldung'])) {
            $module = TrainingModule::find($validated['target_module_id']);
            $data['user_id'] = Auth::id(); // Der Antragsteller ist der User
            $data['target_name'] = Auth::user()->name;
            $data['json_data']['module_name'] = $module->name;
            $data['json_data']['module_id'] = $module->id;
            
            $logAction = ($evaluationType === 'modul_anmeldung') ? 'Antrag auf Modulanmeldung' : 'Antrag auf Prüfungsanmeldung';
            $logDescription = "{$logAction} für '{$module->name}' von {$data['target_name']} eingereicht.";

        } elseif ($evaluationType === 'praktikant') {
            $data['user_id'] = null; 
            $data['target_name'] = $validated['target_name'];
            $logDescription = "Neue Bewertung für Praktikant/in '{$data['target_name']}' ({$evaluationType}) erstellt.";

        } else {
            $data['user_id'] = $validated['user_id'];
            $targetUser = User::find($data['user_id']);
            $data['target_name'] = $targetUser->name;
            $logDescription = "Neue Bewertung für '{$data['target_name']}' ({$evaluationType}) erstellt.";
        }

        $evaluation = Evaluation::create($data);
        
        ActivityLog::create([
             'user_id' => Auth::id(),
             'log_type' => 'EVALUATION',
             'action' => 'CREATED',
             'target_id' => $evaluation->id,
             'description' => $logDescription,
        ]);

        return redirect()->route('forms.evaluations.index')->with('success', 'Formular erfolgreich eingereicht!');
    }

    public function show(Evaluation $evaluation)
    {
        $this->authorize('view', $evaluation); // Nutzt die Policy

        $evaluation->load(['user', 'evaluator']);

        $evaluationData = is_array($evaluation->json_data) 
            ? $evaluation->json_data 
            : json_decode($evaluation->json_data, true);

        $targetName = $evaluation->target_name ?? $evaluation->user?->name ?? 'Unbekannt';

        return view('forms.evaluations.show', compact('evaluation', 'evaluationData', 'targetName'));
    }
}
