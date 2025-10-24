<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use App\Models\User;
use App\Models\TrainingModule;
use App\Models\Evaluation;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Events\PotentiallyNotifiableActionOccurred;
use App\Services\ExamAttemptService; // NEU
use App\Http\Requests\Admin\GenerateExamAttemptRequest; // NEU
use App\Http\Requests\Admin\FinalizeExamRequest; // NEU

class ExamAttemptController extends Controller
{
    protected $attemptService;

    public function __construct(ExamAttemptService $attemptService)
    {
        $this->attemptService = $attemptService;
    }

    /**
     * Zeigt die Liste aller Prüfungsversuche (ehemals 'attemptsIndex').
     */
    public function index()
    {
        $this->authorize('viewAny', ExamAttempt::class);
        
        $attempts = ExamAttempt::with(['exam.trainingModule', 'user'])
                            ->orderBy('updated_at', 'desc')
                            ->paginate(25);
        return view('admin.exams.attempts-index', compact('attempts'));
    }

    /**
     * Erstellt einen neuen Prüfungsversuch (ehemals 'generateLink').
     */
    public function store(GenerateExamAttemptRequest $request)
    {
        // Autorisierung ('generateExamLink') und Validierung (inkl. Check ob Modul Prüfung hat)
        // sind bereits über den Form Request gelaufen.
        
        $validated = $request->validated();
        $module = TrainingModule::find($validated['module_id']);
        $user = User::find($validated['user_id']);
        $evaluation = Evaluation::find($validated['evaluation_id']);

        $attempt = $this->attemptService->generateAttempt($user, $module);

        // Markiere den Antrag als "erledigt"
        $evaluation->update(['status' => 'processed']);

        // Generiere die sichere URL
        $secureUrl = route('exams.take', $attempt); // Nutzt RMB

        // --- ACTIVITY LOG ---
        ActivityLog::create([
            'user_id' => Auth::id(), 
            'log_type' => 'EXAM',
            'action' => 'LINK_GENERATED',
            'target_id' => $attempt->id,
            'description' => "Prüfungslink für '{$module->exam->title}' wurde für {$user->name} generiert."
        ]);

        // --- BENACHRICHTIGUNG VIA EVENT (an den Prüfling) ---
        PotentiallyNotifiableActionOccurred::dispatch(
            'Admin\ExamAttemptController@store', // (ehemals generateLink)
            $user,       // Der Prüfling (Empfänger)
            $attempt,    // Das zugehörige Modell
            Auth::user() // Der Admin (Akteur)
        );

        return back()->with('success', 'Prüfungslink erfolgreich generiert!')
                     ->with('secure_url', $secureUrl);
    }

    /**
     * Zeigt die Ergebnisseite für Admins (ehemals 'result').
     */
    public function show(ExamAttempt $attempt)
    {
        $this->authorize('viewResult', $attempt);
        return view('exams.result', compact('attempt'));
    }

    /**
     * Finalisiert die Bewertung (ehemals 'finalizeEvaluation').
     */
    public function update(FinalizeExamRequest $request, ExamAttempt $attempt)
    {
        // Autorisierung ('setEvaluated') und Validierung sind im Form Request
        
        $validated = $request->validated();
        
        $attempt = $this->attemptService->finalizeAttempt($attempt, $validated);

        // Logeintrag
        $actionDesc = "Prüfung '{$attempt->exam->title}' von {$attempt->user->name} wurde als '{$validated['status_result']}' bewertet. Score: {$validated['final_score']}%";
        ActivityLog::create(['user_id' => Auth::id(), 'log_type' => 'EXAM', 'action' => 'EVALUATED', 'target_id' => $attempt->id, 'description' => $actionDesc]);
        
        // --- BENACHRICHTIGUNG VIA EVENT (an den Prüfling) ---
        PotentiallyNotifiableActionOccurred::dispatch(
            'Admin\ExamAttemptController@update', // (ehemals finalizeEvaluation)
            $attempt->user, // Der Prüfling (Empfänger)
            $attempt,        // Das zugehörige Modell
            Auth::user()     // Der Admin (Akteur)
        );

        return redirect()->route('admin.exams.attempts.index')->with('success', "Prüfung finalisiert.");
    }

    /**
     * Setzt einen Prüfungsversuch zurück.
     */
    public function resetAttempt(ExamAttempt $attempt)
    {
        $this->authorize('resetAttempt', $attempt);

        $this->attemptService->resetAttempt($attempt);

        ActivityLog::create(['user_id' => Auth::id(), 'log_type' => 'EXAM', 'action' => 'RESET', 'target_id' => $attempt->id, 'description' => "Prüfungsversuch #{$attempt->id} von {$attempt->user->name} wurde zurückgesetzt."]);

        PotentiallyNotifiableActionOccurred::dispatch(
            'Admin\ExamAttemptController@resetAttempt',
            $attempt->user, $attempt, Auth::user()
        );

        return back();
    }

    /**
     * Sendet einen Link (oder generiert ihn zur Anzeige).
     */
    public function sendLink(ExamAttempt $attempt)
    {
        $this->authorize('sendLink', $attempt);

        $secureUrl = route('exams.take', $attempt); // Nutzt RMB

        PotentiallyNotifiableActionOccurred::dispatch(
            'Admin\ExamAttemptController@sendLink',
            $attempt->user, $attempt, Auth::user()
        );

        return back()->with('secure_url', $secureUrl);
    }
    
    /**
     * Manuelles Setzen des Scores (alte 'setEvaluated'-Funktion).
     * HINWEIS: Diese Funktion ist jetzt fast identisch mit `update` (finalizeEvaluation).
     * Wir behalten sie, falls sie von woanders genutzt wird, aber `update` ist der bessere Weg.
     */
    public function setEvaluated(Request $request, ExamAttempt $attempt)
    {
        $this->authorize('setEvaluated', $attempt);

        $validated = $request->validate([
            'score' => 'required|integer|min:0|max:100',
        ]);
        
        $isPassed = $validated['score'] >= $attempt->exam->pass_mark;
        $resultText = $isPassed ? 'Bestanden' : 'Nicht bestanden';

        $attempt->update([
            'status' => 'evaluated',
            'score' => $validated['score'],
        ]);

        $message = "Prüfungsversuch #{$attempt->id} von {$attempt->user->name} wurde manuell bewertet: {$resultText} ({$validated['score']}%).";
        ActivityLog::create(['user_id' => Auth::id(), 'log_type' => 'EXAM', 'action' => 'EVALUATED', 'target_id' => $attempt->id, 'description' => $message]);

        PotentiallyNotifiableActionOccurred::dispatch(
            'Admin\ExamAttemptController@setEvaluated',
            $attempt->user, $attempt, Auth::user()
        );

        return back();
    }
}
