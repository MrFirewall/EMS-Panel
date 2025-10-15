<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User; // Muss importiert werden, falls es nicht schon global ist
use App\Models\Evaluation; // Muss importiert werden
use App\Models\ActivityLog;

class ProfileController extends Controller
{
    public function __construct()
    {
        // Schützt die Profilseite. Nur wer 'profile.view' hat, darf sie sehen.
        $this->middleware('can:profile.view')->only('show');
    }
    /**
     * Zeigt das Profil des eingeloggten Benutzers an.
     */
    public function show(User $user)
    {
        $user = Auth::user();
        // Lade alle Relationen außer den Akteneinträgen
        $user->load([
            'examinations', 
            'trainingModules', 
            'vacations',
            'receivedEvaluations' => fn($q) => $q->with('evaluator')->latest(),
        ]);
        
        $serviceRecords = $user->serviceRecords()->with('author')->latest()->get();

        // Diese Logik kann im ProfileController bleiben, da sie nur für das eigene Profil relevant ist.
        $evaluationCounts = $this->calculateEvaluationCounts($user);

        return view('profile.show', compact('user', 'serviceRecords', 'evaluationCounts'));
    }

    /**
     * Berechnet die Anzahl der erhaltenen und verfassten Bewertungen pro Kategorie
     * für den übergebenen Benutzer.
     *
     * @param User $user
     * @return array
     */
    private function calculateEvaluationCounts(User $user): array
    {
        $currentUserId = $user->id;
        $evaluatorId = Auth::id(); // Der aktuell eingeloggte Benutzer (für 'Verfasst')

        // Initialisierung der Zähler
        $counts = ['verfasst' => [], 'erhalten' => [], 'gesamt' => []];
        $typeLabels = ['azubi', 'praktikant', 'mitarbeiter', 'leitstelle'];
        
        foreach ($typeLabels as $type) {
            $counts['verfasst'][$type] = 0;
            $counts['erhalten'][$type] = 0;
        }
        
        // Alle relevanten Bewertungen laden
        $allEvaluations = Evaluation::where('user_id', $currentUserId) // Erhalten
                                    ->orWhere('evaluator_id', $evaluatorId) // Verfasst
                                    ->get();

        foreach ($allEvaluations as $evaluation) {
            $type = $evaluation->evaluation_type;

            if (!isset($counts['verfasst'][$type])) continue; // Ignoriert unbekannte Typen

            // 1. Erhaltene Bewertungen zählen (durch den Profilinhaber)
            if ($evaluation->user_id === $currentUserId) {
                $counts['erhalten'][$type]++;
            }

            // 2. Verfasste Bewertungen zählen (durch den eingeloggten Admin/User)
            if ($evaluation->evaluator_id === $evaluatorId) {
                $counts['verfasst'][$type]++;
            }
        }
        
        return $counts;
    }
}
