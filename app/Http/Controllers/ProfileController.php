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
    public function __construct()
    {
        // Stellt sicher, dass nur eingeloggte Benutzer Zugriff haben
        $this->middleware('auth');
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
            'trainingModules.assigner',
            'vacations',
            'receivedEvaluations' => fn($q) => $q->with('evaluator')->latest(),
        ]);
        
        // NEU: Laden Sie die Prüfungsversuche
        $examAttempts = ExamAttempt::where('user_id', $user->id)
                                    ->with('exam.trainingModule') // Laden des zugehörigen Moduls und der Prüfung
                                    ->latest('completed_at')
                                    ->get();
        
        $serviceRecords = $user->serviceRecords()->with('author')->latest()->get();
        $evaluationCounts = $this->calculateEvaluationCounts($user); // Korrigierte Berechnung

        // Die neue Stundenberechnung aus dem User-Model aufrufen
        $hourData = $user->calculateDutyHours();
        $weeklyHours = $user->calculateWeeklyHoursSinceEntry();
        
        return view('profile.show', compact(
            'user', 
            'serviceRecords', 
            'evaluationCounts',
            'hourData',
            'weeklyHours',
            'examAttempts'
        ));
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
