<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Evaluation;
use App\Models\ExamAttempt;
use App\Models\Pivots\TrainingModuleUser;

class ProfileController extends Controller
{
    /**
     * Definiert die unsichtbare Super-Admin Rolle.
     * @var string
     */
    private $superAdminRole = 'Super-Admin'; // NEU

    public function __construct()
    {
        // Stellt sicher, dass nur eingeloggte Benutzer Zugriff haben
        $this->middleware('auth');
    }

    /**
     * KORRIGIERT: Hilfsfunktion, die die Super-Admin Rolle aus der Anzeige entfernt.
     * Klont den User, um das Original (z.B. Auth::user()) nicht zu verändern.
     */
    private function filterSuperAdminFromRoles(User $user): User
    {
        // KORREKTUR: Klonen, um das Originalobjekt nicht zu verändern (wichtig für Auth::user())
        $viewUser = clone $user;

        if ($viewUser->relationLoaded('roles')) {
            $filteredRoles = $viewUser->roles->reject(function ($role) {
                return $role->name === $this->superAdminRole;
            });
            // Modifiziere nur den Klon
            $viewUser->setRelation('roles', $filteredRoles);
        }
        // Gib den modifizierten Klon zurück
        return $viewUser;
    }

    /**
     * Zeigt das Profil des aktuell eingeloggten Benutzers an.
     * Es wird kein User-Objekt mehr aus der Route erwartet.
     */
    public function show()
    {
        // Hole den eingeloggten Benutzer direkt
        $user = Auth::user();

        // Laden Sie alle benötigten Relationen
        $user->load([
            // 'trainingModules' HIER ENTFERNEN!
            'vacations',
            'receivedEvaluations' => fn($q) => $q->with('evaluator')->latest(),
            'roles' // NEU: Sicherstellen, dass Rollen geladen sind
        ]);

        // 1. Lade die Module
        $user->load('trainingModules');

        // 2. Lade die 'assigner'-Beziehung AUF die Pivot-Objekte
        if ($user->trainingModules->isNotEmpty()) {
            // 1. Hole die Sammlung der Pivot-Objekte (als normale Collection)
            $pivots = $user->trainingModules->pluck('pivot'); 
            
            // 2. Erstelle eine NEUE Eloquent Collection daraus und lade die Beziehung
            (new \Illuminate\Database\Eloquent\Collection($pivots))->load('assigner');
        }
        
        // NEU: Laden Sie die Prüfungsversuche (dein bestehender Code)
        $examAttempts = ExamAttempt::where('user_id', $user->id)
                                    ->with('exam.trainingModule')
                                    ->latest('completed_at')
                                    ->get();
        
        $serviceRecords = $user->serviceRecords()->with('author')->latest()->get();
        $evaluationCounts = $this->calculateEvaluationCounts($user); // Korrigierte Berechnung

        // Die neue Stundenberechnung aus dem User-Model aufrufen
        $hourData = $user->calculateDutyHours();
        $weeklyHours = $user->calculateWeeklyHoursSinceEntry();
        
        // KORREKTUR: Wende den "sicheren" Filter an und übergib den Klon an die View
        // Das Original $user (Auth::user()) bleibt unberührt, was die Navigation rettet.
        $viewUser = $this->filterSuperAdminFromRoles($user);

        // KORREKTUR: 'compact' kann keine assoziativen Zuweisungen ('=>') annehmen.
        // Wir übergeben das Array direkt.
        return view('profile.show', [
            'user' => $viewUser, // KORREKTUR: Übergib den gefilterten Klon
            'serviceRecords' => $serviceRecords, 
            'evaluationCounts' => $evaluationCounts,
            'hourData' => $hourData,
            'weeklyHours' => $weeklyHours,
            'examAttempts' => $examAttempts
        ]);
    }

    /**
     * Berechnet die Anzahl der Bewertungen.
     * Diese Logik ist privat und nur für diesen Controller relevant.
     * WICHTIG: Die Zählung wird jetzt über separate Queries durchgeführt, um Korrektheit zu garantieren.
     */
    private function calculateEvaluationCounts(User $user): array
    {
        $typeLabels = ['azubi', 'praktikant', 'mitarbeiter', 'leitstelle'];
        $counts = ['verfasst' => [], 'erhalten' => []];

        // 1. Zählungen des Profilbesitzers ($user) - ERHALTEN
        $receivedCounts = Evaluation::selectRaw('evaluation_type, count(*) as count')
                                    ->where('user_id', $user->id)
                                    ->whereIn('evaluation_type', $typeLabels)
                                    ->groupBy('evaluation_type')
                                    ->pluck('count', 'evaluation_type');

        // 2. Zählungen des angemeldeten Benutzers (Auth::user()) - VERFASST
        $authoredCounts = Evaluation::selectRaw('evaluation_type, count(*) as count')
                                    ->where('evaluator_id', Auth::id())
                                    ->whereIn('evaluation_type', $typeLabels)
                                    ->groupBy('evaluation_type')
                                    ->pluck('count', 'evaluation_type');

        // Initialisiere mit 0 und fülle die Ergebnisse auf
        foreach ($typeLabels as $type) {
            $counts['erhalten'][$type] = $receivedCounts->get($type, 0);
            $counts['verfasst'][$type] = $authoredCounts->get($type, 0);
        }
        
        return $counts;
    }
}

