<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Evaluation;

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

        $user->load([
            'examinations', 
            'trainingModules', 
            'vacations',
            'receivedEvaluations' => fn($q) => $q->with('evaluator')->latest(),
        ]);
        
        $serviceRecords = $user->serviceRecords()->with('author')->latest()->get();
        $evaluationCounts = $this->calculateEvaluationCounts($user);

        // Die neue Stundenberechnung aus dem User-Model aufrufen
        $hourData = $user->calculateDutyHours();
    $weeklyHours = $user->calculateWeeklyHoursSinceEntry();
        return view('profile.show', compact(
            'user', 
            'serviceRecords', 
            'evaluationCounts',
            'hourData',
            'weeklyHours'
        ));
    }

    /**
     * Berechnet die Anzahl der Bewertungen.
     * Diese Logik ist privat und nur für diesen Controller relevant.
     */
    private function calculateEvaluationCounts(User $user): array
    {
        $currentUserId = $user->id;
        $evaluatorId = Auth::id();

        $counts = ['verfasst' => [], 'erhalten' => []];
        $typeLabels = ['azubi', 'praktikant', 'mitarbeiter', 'leitstelle'];
        
        foreach ($typeLabels as $type) {
            $counts['verfasst'][$type] = 0;
            $counts['erhalten'][$type] = 0;
        }
        
        $allEvaluations = Evaluation::where('user_id', $currentUserId)
                                      ->orWhere('evaluator_id', $evaluatorId)
                                      ->get();

        foreach ($allEvaluations as $evaluation) {
            $type = $evaluation->evaluation_type;

            if (!isset($counts['verfasst'][$type])) continue;

            if ($evaluation->user_id === $currentUserId) {
                $counts['erhalten'][$type]++;
            }

            if ($evaluation->evaluator_id === $evaluatorId) {
                $counts['verfasst'][$type]++;
            }
        }
        
        return $counts;
    }
}
