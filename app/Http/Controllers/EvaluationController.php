<?php

namespace App\Http\Controllers;

use App\Models\Evaluation;
use App\Models\User;
use App\Models\ActivityLog;
use App\Models\TrainingModule; // Wird für Model-Bezüge benötigt
use App\Models\Exam; // Wird für Model-Bezüge benötigt
use App\Models\ExamAttempt; // Wird für Berechtigungsprüfung benötigt
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Events\PotentiallyNotifiableActionOccurred;

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
    }

    /**
     * Zeigt die Übersichtsseite für ALLE Anträge und letzte Bewertungen an.
     */
    public function index()
    {
        $canViewAll = Auth::user()->can('evaluations.view.all');
        $userId = Auth::id();

        // 1. Lade ALLE Anträge (nicht nur 'pending'), paginiert
        $applicationsQuery = Evaluation::whereIn('evaluation_type', self::$applicationTypes)
                                        ->latest('created_at'); // Neueste zuerst

        if (!$canViewAll) {
            $applicationsQuery->where('user_id', $userId); // Nur eigene Anträge
        }
        $applications = $applicationsQuery->with('user')->paginate(20, ['*'], 'applicationsPage');

        // 2. Lade letzte eingereichte Bewertungen (paginiert)
        $evaluationsQuery = Evaluation::whereIn('evaluation_type', self::$evaluationTypes)
                                       ->latest('created_at'); // Neueste zuerst
         if (!$canViewAll) {
            $evaluationsQuery->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                      ->orWhere('evaluator_id', $userId);
            });
        }
        $evaluations = $evaluationsQuery->with(['user', 'evaluator'])->paginate(15, ['*'], 'evaluationsPage');

        // 3. Lade alle User für das "Link generieren"-Modal (nur wenn benötigt und berechtigt)
        $usersForModal = collect();
         if ($canViewAll && Auth::user()->can('generateExamLink', ExamAttempt::class)) {
             $usersForModal = User::orderBy('name')->get(['id', 'name']);
         }

        // Counts optional
        $counts = $this->getEvaluationCounts();

        // Übergabe der Daten an die View (ohne Module & Exams)
        return view('forms.evaluations.index', compact(
            'applications',
            'evaluations',
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
        $relevantTypes = array_merge(self::$applicationTypes, self::$evaluationTypes);
        $counts = ['verfasst' => [], 'erhalten' => [], 'gesamt' => []];

        foreach ($relevantTypes as $type) {
            $counts['verfasst'][$type] = 0;
            $counts['erhalten'][$type] = 0;
            $counts['gesamt'][$type] = 0;
        }

        $allCounts = Evaluation::selectRaw('evaluation_type, user_id, evaluator_id, count(*) as count')
                                ->whereIn('evaluation_type', $relevantTypes)
                                ->groupBy('evaluation_type', 'user_id', 'evaluator_id')
                                ->get();

        foreach ($allCounts as $countData) {
            $type = $countData->evaluation_type;
            if (!isset($counts['gesamt'][$type])) continue;

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
        $users = User::role('trainee')->orderBy('name')->get(['id', 'name']);
        return view('forms.evaluations.azubi', ['users' => $users, 'evaluationType' => 'azubi']);
    }

    public function praktikant()
    {
        return view('forms.evaluations.praktikant', ['evaluationType' => 'praktikant']);
    }

    public function leitstelle()
    {
        $users = User::orderBy('name')->get(['id', 'name']);
        return view('forms.evaluations.leitstelle', ['users' => $users, 'evaluationType' => 'leitstelle']);
    }

    public function mitarbeiter()
    {
        $exemptRoles = ['trainee', 'praktikant'];
        $users = User::whereDoesntHave('roles', function ($query) use ($exemptRoles) {
            $query->whereIn('name', $exemptRoles);
        })->orderBy('name')->get(['id', 'name']);
        return view('forms.evaluations.mitarbeiter', ['users' => $users, 'evaluationType' => 'mitarbeiter']);
    }

    public function modulAnmeldung()
    {
        $existingModuleIds = Auth::user()->trainingModules()->pluck('training_module_id');
        $availableModules = TrainingModule::whereNotIn('id', $existingModuleIds)->orderBy('name')->get(['id', 'name']);

        return view('forms.evaluations.modul_anmeldung', [
            'evaluationType' => 'modul_anmeldung',
            'modules' => $availableModules
        ]);
    }

    public function pruefungsAnmeldung()
    {
        $availableExams = Exam::orderBy('title')->get(['id', 'title']);
        return view('forms.evaluations.pruefung_anmeldung', [
            'evaluationType' => 'pruefung_anmeldung',
            'exams' => $availableExams
        ]);
    }

    // =========================================================================
    // DATEN SPEICHERN & DETAILANSICHT
    // =========================================================================

    public function store(Request $request)
    {
        $evaluationType = $request->input('evaluation_type');

        $validationRules = [
            'evaluation_type' => 'required|in:' . implode(',', self::$allTypeLabels),
            'description' => 'nullable|string|max:5000',
            'evaluation_date' => 'required|date',
            'period' => 'required|string',
            'data' => 'nullable|array',
        ];

        // Typspezifische Validierung
        if ($evaluationType === 'modul_anmeldung') {
            $validationRules['target_module_id'] = 'required|exists:training_modules,id';
        } elseif ($evaluationType === 'pruefung_anmeldung') {
            $validationRules['target_exam_id'] = 'required|exists:exams,id';
        } elseif ($evaluationType === 'praktikant') {
            $validationRules['target_name'] = 'required|string|max:255';
        } elseif (in_array($evaluationType, self::$evaluationTypes)) {
            $validationRules['user_id'] = 'required|exists:users,id';
        }

        $validated = $request->validate($validationRules);

        $data = [
            'evaluator_id' => Auth::id(),
            'evaluation_type' => $validated['evaluation_type'],
            'evaluation_date' => $validated['evaluation_date'],
            'period' => $validated['period'],
            'json_data' => $validated['data'] ?? [],
            'description' => $validated['description'] ?? null,
            'status' => 'pending',
        ];

        $logDescription = '';
        $relatedModel = null;

        if ($evaluationType === 'modul_anmeldung') {
            $module = TrainingModule::find($validated['target_module_id']);
            $data['user_id'] = Auth::id();
            $data['target_name'] = Auth::user()->name;
            $data['json_data']['module_id'] = $module->id;
            $data['json_data']['module_name'] = $module->name;
            $logDescription = "Antrag auf Modulanmeldung für '{$module->name}' von {$data['target_name']} eingereicht.";
            $relatedModel = $module;

        } elseif ($evaluationType === 'pruefung_anmeldung') {
            $exam = Exam::find($validated['target_exam_id']);
            $data['user_id'] = Auth::id();
            $data['target_name'] = Auth::user()->name;
            $data['json_data']['exam_id'] = $exam->id;
            $data['json_data']['exam_title'] = $exam->title;
            $logDescription = "Antrag auf Prüfungsanmeldung für '{$exam->title}' von {$data['target_name']} eingereicht.";
            $relatedModel = $exam;

        } elseif ($evaluationType === 'praktikant') {
            $data['user_id'] = null;
            $data['target_name'] = $validated['target_name'];
            $logDescription = "Neue Bewertung für Praktikant/in '{$data['target_name']}' ({$evaluationType}) erstellt.";
            $data['status'] = 'processed'; // Bewertungen sind direkt "erledigt"

        } elseif (in_array($evaluationType, self::$evaluationTypes)) {
            $data['user_id'] = $validated['user_id'];
            $targetUser = User::find($data['user_id']);
            $data['target_name'] = $targetUser->name;
            $logDescription = "Neue Bewertung für '{$data['target_name']}' ({$evaluationType}) erstellt.";
            $data['status'] = 'processed';
        }

        $evaluation = Evaluation::create($data);

        ActivityLog::create([
             'user_id' => Auth::id(),
             'log_type' => 'EVALUATION',
             'action' => 'CREATED',
             'target_id' => $evaluation->id,
             'description' => $logDescription,
         ]);

        if (in_array($evaluationType, self::$applicationTypes)) {
             PotentiallyNotifiableActionOccurred::dispatch(
                 'EvaluationController@store',
                 Auth::user(),
                 $evaluation,
                 Auth::user(),
                 ['related_model_type' => $relatedModel ? get_class($relatedModel) : null]
             );
        }

        return redirect()->back();
    }

    public function show(Evaluation $evaluation)
    {
        $evaluation->load(['user', 'evaluator']);

        $evaluationData = is_array($evaluation->json_data)
            ? $evaluation->json_data
            : json_decode($evaluation->json_data, true) ?? [];

        $targetName = $evaluation->target_name ?? $evaluation->user?->name ?? 'Unbekannt';

        $relatedItem = null;
        if ($evaluation->evaluation_type === 'modul_anmeldung' && isset($evaluationData['module_id'])) {
            $relatedItem = TrainingModule::find($evaluationData['module_id']);
        } elseif ($evaluation->evaluation_type === 'pruefung_anmeldung' && isset($evaluationData['exam_id'])) {
            $relatedItem = Exam::find($evaluationData['exam_id']);
        }

        return view('forms.evaluations.show', compact('evaluation', 'evaluationData', 'targetName', 'relatedItem'));
    }

    /**
     * NEU: Löscht einen Antrag oder eine Bewertung.
     */
    public function destroy(Evaluation $evaluation)
    {
        // Die Autorisierung erfolgt automatisch über authorizeResource und die EvaluationPolicy.
        // $this->authorize('delete', $evaluation);

        $logDescription = "Eintrag #{$evaluation->id} (Typ: {$evaluation->evaluation_type})";
        $applicantName = $evaluation->target_name ?? $evaluation->user->name ?? 'Unbekannt';

        if (in_array($evaluation->evaluation_type, self::$applicationTypes)) {
            $subject = $evaluation->json_data['module_name'] ?? $evaluation->json_data['exam_title'] ?? 'N/A';
            $logDescription = "Antrag '{$subject}' von {$applicantName} (ID: {$evaluation->id}) wurde gelöscht.";
        } else {
            $logDescription = "Bewertung (Typ: {$evaluation->evaluation_type}) für {$applicantName} (ID: {$evaluation->id}) wurde gelöscht.";
        }

        // Kopie der Daten für das Event erstellen, bevor gelöscht wird
        $deletedData = $evaluation->toArray();
        $triggeringUser = $evaluation->user ?? Auth::user(); // Der Antragsteller, falls vorhanden

        $evaluation->delete();

        // Activity Log Eintrag
        ActivityLog::create([
             'user_id' => Auth::id(), // Der Admin, der löscht
             'log_type' => 'EVALUATION',
             'action' => 'DELETED',
             'target_id' => $deletedData['id'], // ID aus den alten Daten nehmen
             'description' => $logDescription,
         ]);

        // Benachrichtigung via Event
        PotentiallyNotifiableActionOccurred::dispatch(
            'EvaluationController@destroy',
            $triggeringUser,    // Der ursprüngliche Antragsteller
            (object) $deletedData, // Die gelöschte Evaluation als relatedModel
            Auth::user(),       // Der Admin (actor user)
            ['description' => $logDescription] // Zusätzliche Daten
        );

        return redirect()->route('forms.evaluations.index')->with('success', 'Eintrag erfolgreich gelöscht.');
    }
}
