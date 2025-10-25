<?php

namespace App\Http\Controllers;

use App\Models\Evaluation;
use App\Models\User;
use App\Models\ActivityLog;
use App\Models\TrainingModule;
use App\Models\Exam; // NEU: Exam importieren
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Events\PotentiallyNotifiableActionOccurred;
// use App\Notifications\GeneralNotification; // Nicht direkt verwendet
// use Illuminate\Support\Facades\Notification; // Nicht direkt verwendet

class EvaluationController extends Controller
{
    // Statische Arrays für Konsistenz
    public static array $grades = ['Sehr Gut', 'Gut', 'Befriedigend', 'Ausreichend', 'Mangelhaft', 'Ungenügend', 'Nicht feststellbar'];
    public static array $periods = ['00 - 06 Uhr', '06 - 12 Uhr', '12 - 18 Uhr', '18 - 00 Uhr'];

    // Typen für Anträge und Bewertungen
    public static array $applicationTypes = ['modul_anmeldung', 'pruefung_anmeldung'];
    public static array $evaluationTypes = ['azubi', 'praktikant', 'mitarbeiter', 'leitstelle', 'gutachten', 'anmeldung'];
    // Alle Typen kombiniert für Validierung
    public static array $allTypeLabels = [
        'azubi', 'praktikant', 'mitarbeiter', 'leitstelle', 'gutachten',
        'anmeldung', 'modul_anmeldung', 'pruefung_anmeldung'
    ];


    public function __construct()
    {
        // Policy-basierte Autorisierung
        $this->authorizeResource(Evaluation::class, 'evaluation');
        // Spezifische Berechtigungen für Formularansichten (optional, falls nicht von 'create' abgedeckt)
        // $this->middleware('can:create,App\Models\Evaluation')->only([...]);
    }

    /**
     * Zeigt die Übersichtsseite für ALLE Anträge und letzte Bewertungen an.
     */
Okay, ich verstehe. Du möchtest die Tabs zurückhaben, aber diesmal getrennt nach Anträgen, Bewertungen, Modulen und Prüfungen. Zusätzlich soll der Modul-Tab die Möglichkeit bieten, User direkt zuzuweisen oder den Status zu ändern.

Das direkte Zuweisen/Ändern von Usern innerhalb der Modul-Liste ist etwas komplexer, da man für jedes Modul potenziell alle User laden oder eine Suchfunktion/Modal braucht. Ich baue es erstmal so um, dass du Links zum Bearbeiten des Moduls hast und einen neuen Button, der zu einer (zukünftigen) dedizierten Seite führt, um User für dieses spezifische Modul zu verwalten.

1. Controller: app/Http/Controllers/EvaluationController.php (angepasste index-Methode)

Wir holen wieder alle Daten, wie in der Version mit den vielen Tabs.

PHP

<?php

namespace App\Http{forward-slash}Controllers;

use App\Models\Evaluation;
use App\Models\User;
use App\Models\ActivityLog;
use App\Models\TrainingModule;
use App\Models\Exam;
use App\Models\ExamAttempt; // Importieren für Berechtigungsprüfung
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Events\PotentiallyNotifiableActionOccurred;

class EvaluationController extends Controller
{
    // ... (Statische Arrays bleiben gleich) ...
    public static array $applicationTypes = ['modul_anmeldung', 'pruefung_anmeldung'];
    public static array $evaluationTypes = ['azubi', 'praktikant', 'mitarbeiter', 'leitstelle', 'gutachten', 'anmeldung'];
    public static array $allTypeLabels = ['azubi', 'praktikant', 'mitarbeiter', 'leitstelle', 'gutachten', 'anmeldung', 'modul_anmeldung', 'pruefung_anmeldung'];


    public function __construct()
    {
        // Policy-basierte Autorisierung (viewAny wird hier geprüft)
        $this->authorizeResource(Evaluation::class, 'evaluation');
    }

    /**
     * Zeigt die Übersichtsseite mit Tabs für Anträge, Bewertungen, Module, Prüfungen.
     */
    public function index()
    {
        $canViewAll = Auth::user()->can('evaluations.view.all'); // Nützlich für Anträge/Bewertungen
        $userId = Auth::id();

        // 1. Lade ALLE Anträge (paginiert)
        $applicationsQuery = Evaluation::whereIn('evaluation_type', self::$applicationTypes)
                                        ->latest('created_at');
        if (!$canViewAll) {
            $applicationsQuery->where('user_id', $userId);
        }
        $applications = $applicationsQuery->with('user')->paginate(15, ['*'], 'applicationsPage');

        // 2. Lade letzte Bewertungen (paginiert)
        $evaluationsQuery = Evaluation::whereIn('evaluation_type', self::$evaluationTypes)
                                       ->latest('created_at');
         if (!$canViewAll) {
            $evaluationsQuery->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                      ->orWhere('evaluator_id', $userId);
            });
        }
        $evaluations = $evaluationsQuery->with(['user', 'evaluator'])->paginate(15, ['*'], 'evaluationsPage');

        // 3. Lade ALLE Trainingsmodule (paginiert, falls Berechtigung vorhanden)
        $trainingModules = collect();
        if (Auth::user()->can('training.view')) { // Berechtigung anpassen
             // Lade die Anzahl der zugewiesenen Benutzer mit
             $trainingModules = TrainingModule::withCount('users')->orderBy('category')->orderBy('name')->paginate(15, ['*'], 'modulesPage');
        }

        // 4. Lade ALLE Prüfungen (paginiert, falls Berechtigung vorhanden)
        $exams = collect();
        if (Auth::user()->can('exams.manage')) { // Berechtigung anpassen
            $exams = Exam::withCount('questions')->latest()->paginate(15, ['*'], 'examsPage');
        }

        // 5. Lade alle User für das "Link generieren"-Modal (falls Berechtigung vorhanden)
        $usersForModal = collect();
         if (Auth::user()->can('generateExamLink', ExamAttempt::class)) {
             $usersForModal = User::orderBy('name')->get(['id', 'name']);
         }

        // Counts optional
        $counts = $this->getEvaluationCounts();

        // Übergabe aller Daten an die View
        return view('forms.evaluations.index', compact(
            'applications',
            'evaluations',
            'trainingModules', // Wieder hinzugefügt
            'exams',           // Wieder hinzugefügt
            'counts',
            'canViewAll',
            'usersForModal'
        ));
    }

    /**
     * Zählt die verschiedenen Formulartypen für die Übersichtsseite.
     */
    private function getEvaluationCounts()
    {
        $currentUserId = Auth::id();
        // Zähle nur die relevanten Bewertungstypen
        $relevantTypes = array_merge(self::$applicationTypes, self::$evaluationTypes);
        $counts = ['verfasst' => [], 'erhalten' => [], 'gesamt' => []];

        foreach ($relevantTypes as $type) {
            $counts['verfasst'][$type] = 0;
            $counts['erhalten'][$type] = 0;
            $counts['gesamt'][$type] = 0;
        }

        // Effizientere Zählung mit DB-Abfragen
        $allCounts = Evaluation::selectRaw('evaluation_type, user_id, evaluator_id, count(*) as count')
                                ->whereIn('evaluation_type', $relevantTypes)
                                ->groupBy('evaluation_type', 'user_id', 'evaluator_id')
                                ->get();

        foreach ($allCounts as $countData) {
            $type = $countData->evaluation_type;
            if (!isset($counts['gesamt'][$type])) continue; // Überspringe unbekannte Typen

            $counts['gesamt'][$type] += $countData->count;
            if ($countData->evaluator_id === $currentUserId) {
                 $counts['verfasst'][$type] += $countData->count;
             }
            if ($countData->user_id === $currentUserId) {
                 $counts['erhalten'][$type] += $countData->count;
             }
        }
        return $counts;
    }

    // =========================================================================
    // FORMULAR-ANSICHTEN
    // =========================================================================

    public function azubi()
    {
        // authorize('create') wird durch authorizeResource abgedeckt
        $users = User::role('emt-trainee')->orderBy('name')->get(['id', 'name']); // Nur nötige Spalten laden
        return view('forms.evaluations.azubi', ['users' => $users, 'evaluationType' => 'azubi']);
    }

    public function praktikant()
    {
        // authorize('create') wird durch authorizeResource abgedeckt
        // Keine User-Auswahl nötig, da Name manuell eingegeben wird
        return view('forms.evaluations.praktikant', ['evaluationType' => 'praktikant']);
    }

    public function leitstelle()
    {
       // authorize('create') wird durch authorizeResource abgedeckt
        $users = User::orderBy('name')->get(['id', 'name']); // Nur nötige Spalten laden
        return view('forms.evaluations.leitstelle', ['users' => $users, 'evaluationType' => 'leitstelle']);
    }

    public function mitarbeiter()
    {
        // authorize('create') wird durch authorizeResource abgedeckt
        $exemptRoles = ['emt-trainee', 'praktikant']; // Rollen, die ausgeschlossen werden sollen
        $users = User::whereDoesntHave('roles', function ($query) use ($exemptRoles) {
            $query->whereIn('name', $exemptRoles);
        })->orderBy('name')->get(['id', 'name']); // Nur nötige Spalten laden
        return view('forms.evaluations.mitarbeiter', ['users' => $users, 'evaluationType' => 'mitarbeiter']);
    }

    public function modulAnmeldung()
    {
       // authorize('create') wird durch authorizeResource abgedeckt
        // IDs der Module, für die der User *irgendeinen* Eintrag hat (unabhängig vom Status)
        $existingModuleIds = Auth::user()->trainingModules()->pluck('training_module_id');
        // Lade nur Module, für die der User *noch keinen* Eintrag hat
        $availableModules = TrainingModule::whereNotIn('id', $existingModuleIds)->orderBy('name')->get(['id', 'name']);

        return view('forms.evaluations.modul_anmeldung', [
            'evaluationType' => 'modul_anmeldung',
            'modules' => $availableModules
        ]);
    }

    public function pruefungsAnmeldung() // <<<--- HIER ÄNDERUNGEN
    {
        // authorize('create') wird durch authorizeResource abgedeckt

        // Lade ALLE verfügbaren Prüfungen, statt Module
        $availableExams = Exam::orderBy('title')->get(['id', 'title']);

        return view('forms.evaluations.pruefung_anmeldung', [
            'evaluationType' => 'pruefung_anmeldung',
            // ALT: 'modules' => $modulesInTraining
            'exams' => $availableExams // Übergebe die Prüfungen an die View
        ]);
    }

    // =========================================================================
    // DATEN SPEICHERN & DETAILANSICHT
    // =========================================================================

    public function store(Request $request) // <<<--- HIER ÄNDERUNGEN
    {
        // authorize('create') wird durch authorizeResource abgedeckt

        $evaluationType = $request->input('evaluation_type');

        // Basis-Validierungsregeln
        $validationRules = [
            'evaluation_type' => 'required|in:' . implode(',', self::$allTypeLabels),
            'description' => 'nullable|string|max:5000', // Max Länge hinzugefügt
            'evaluation_date' => 'required|date',
            'period' => 'required|string', // Beibehalten, falls für andere Typen nötig
            'data' => 'nullable|array', // Für JSON-Daten
        ];

        // Typspezifische Validierung
        if ($evaluationType === 'modul_anmeldung') {
            $validationRules['target_module_id'] = 'required|exists:training_modules,id';
            // Optional: Prüfen, ob User das Modul nicht schon hat
            // $validationRules['target_module_id'] .= '|unique:training_module_user,training_module_id,NULL,id,user_id,' . Auth::id();
        } elseif ($evaluationType === 'pruefung_anmeldung') { // NEUE Validierung für Prüfung
            $validationRules['target_exam_id'] = 'required|exists:exams,id'; // Prüft auf Exam-ID
        } elseif ($evaluationType === 'praktikant') {
            $validationRules['target_name'] = 'required|string|max:255';
            // Spezifische Daten validieren, falls nötig (Beispiel)
            // $validationRules['data.feedback'] = 'required|string';
        } elseif (in_array($evaluationType, self::$evaluationTypes)) { // Für alle anderen Bewertungs-Typen
            $validationRules['user_id'] = 'required|exists:users,id';
             // Spezifische Daten validieren, falls nötig (Beispiel)
             // if($evaluationType === 'azubi') {
             //    $validationRules['data.skill_level'] = 'required|integer|min:1|max:5';
             // }
        }

        $validated = $request->validate($validationRules);

        // Daten für die Erstellung vorbereiten
        $data = [
            'evaluator_id' => Auth::id(), // Der Ersteller
            'evaluation_type' => $validated['evaluation_type'],
            'evaluation_date' => $validated['evaluation_date'],
            'period' => $validated['period'],
            'json_data' => $validated['data'] ?? [], // Nimmt zusätzliche Daten aus dem Formular auf
            'description' => $validated['description'] ?? null,
            'status' => 'pending', // Standardmäßig auf 'pending' für Anträge, für Bewertungen ggf. anpassen?
        ];

        $logDescription = '';
        $relatedModel = null; // Für das Event

        // Typspezifische Datenaufbereitung und Log-Beschreibung
        if ($evaluationType === 'modul_anmeldung') {
            $module = TrainingModule::find($validated['target_module_id']);
            $data['user_id'] = Auth::id(); // Antragsteller
            $data['target_name'] = Auth::user()->name; // Name des Antragstellers speichern
            // Relevante Modulinfos im JSON speichern
            $data['json_data']['module_id'] = $module->id;
            $data['json_data']['module_name'] = $module->name;
            $logDescription = "Antrag auf Modulanmeldung für '{$module->name}' von {$data['target_name']} eingereicht.";
            $relatedModel = $module; // Modul als relatedModel für Event

        } elseif ($evaluationType === 'pruefung_anmeldung') { // NEUE Logik für Prüfungsanmeldung
            $exam = Exam::find($validated['target_exam_id']); // Prüfung finden
            $data['user_id'] = Auth::id(); // Antragsteller
            $data['target_name'] = Auth::user()->name; // Name des Antragstellers speichern
            // Relevante Prüfungsinfos im JSON speichern
            $data['json_data']['exam_id'] = $exam->id;
            $data['json_data']['exam_title'] = $exam->title;
            $logDescription = "Antrag auf Prüfungsanmeldung für '{$exam->title}' von {$data['target_name']} eingereicht.";
            $relatedModel = $exam; // Prüfung als relatedModel für Event

        } elseif ($evaluationType === 'praktikant') {
            $data['user_id'] = null; // Kein registrierter User
            $data['target_name'] = $validated['target_name'];
            $logDescription = "Neue Bewertung für Praktikant/in '{$data['target_name']}' ({$evaluationType}) erstellt.";
            $data['status'] = 'processed'; // Bewertungen sind direkt "erledigt"

        } elseif (in_array($evaluationType, self::$evaluationTypes)) { // Andere Bewertungen
            $data['user_id'] = $validated['user_id']; // Der bewertete User
            $targetUser = User::find($data['user_id']);
            $data['target_name'] = $targetUser->name;
            $logDescription = "Neue Bewertung für '{$data['target_name']}' ({$evaluationType}) erstellt.";
            $data['status'] = 'processed'; // Bewertungen sind direkt "erledigt"
        }

        $evaluation = Evaluation::create($data);

        // Activity Log Eintrag
        ActivityLog::create([
             'user_id' => Auth::id(),
             'log_type' => 'EVALUATION',
             'action' => 'CREATED',
             'target_id' => $evaluation->id,
             'description' => $logDescription,
         ]);

        // Benachrichtigung via Event (nur bei Anträgen)
        if (in_array($evaluationType, self::$applicationTypes)) {
             PotentiallyNotifiableActionOccurred::dispatch(
                 'EvaluationController@store',
                 Auth::user(),      // Der Antragsteller (triggering user)
                 $evaluation,      // Die erstellte Evaluation
                 Auth::user(),      // Der Antragsteller (actor user)
                 ['related_model_type' => $relatedModel ? get_class($relatedModel) : null] // Typ des relatedModel übergeben
             );
        }
        // Ggf. anderes Event für erstellte Bewertungen auslösen

        return redirect()->route('forms.evaluations.index'); // Ohne success
    }

    public function show(Evaluation $evaluation)
    {
        // authorize('view') wird durch authorizeResource abgedeckt
        // $this->authorize('view', $evaluation);

        $evaluation->load(['user', 'evaluator']); // Lade Relationen

        // JSON-Daten sicher dekodieren (falls es kein Array ist)
        $evaluationData = is_array($evaluation->json_data)
            ? $evaluation->json_data
            : json_decode($evaluation->json_data, true) ?? []; // Fallback auf leeres Array

        // Zielnamen bestimmen
        $targetName = $evaluation->target_name ?? $evaluation->user?->name ?? 'Unbekannt';

        // Lade ggf. das zugehörige Modul oder die Prüfung für die Anzeige
        $relatedItem = null;
        if ($evaluation->evaluation_type === 'modul_anmeldung' && isset($evaluationData['module_id'])) {
            $relatedItem = TrainingModule::find($evaluationData['module_id']);
        } elseif ($evaluation->evaluation_type === 'pruefung_anmeldung' && isset($evaluationData['exam_id'])) {
            $relatedItem = Exam::find($evaluationData['exam_id']);
        }


        return view('forms.evaluations.show', compact('evaluation', 'evaluationData', 'targetName', 'relatedItem'));
    }
}
