<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use App\Models\User;
// use App\Models\TrainingModule; // Nicht mehr benötigt
use App\Models\Exam; // NEU
use App\Models\Evaluation;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Events\PotentiallyNotifiableActionOccurred;
use App\Services\ExamAttemptService;
use App\Http\Requests\Admin\GenerateExamAttemptRequest;
use App\Http\Requests\Admin\FinalizeExamRequest;

class ExamAttemptController extends Controller
{
    protected $attemptService;

    public function __construct(ExamAttemptService $attemptService)
    {
        $this->attemptService = $attemptService;
        // Middleware für Berechtigungen
        $this->middleware('can:viewAny,' . ExamAttempt::class)->only('index');
        // Autorisierung für andere Methoden erfolgt direkt in den Methoden
    }

    public function index()
    {
        // $this->authorize('viewAny', ExamAttempt::class); // Bereits durch Middleware

        // KORREKTUR: Lade den Bewerter (evaluator) direkt mit, für die Ansicht
        $attempts = ExamAttempt::with(['exam', 'user', 'evaluator']) 
                            ->orderBy('updated_at', 'desc')
                            ->paginate(25);
        return view('admin.exams.attempts-index', compact('attempts'));
    }

    public function store(GenerateExamAttemptRequest $request)
    {
        $validated = $request->validated();
        $exam = Exam::findOrFail($validated['exam_id']); // Finde die Prüfung
        $user = User::findOrFail($validated['user_id']); // Finde den User
        $evaluation = isset($validated['evaluation_id']) ? Evaluation::find($validated['evaluation_id']) : null; // Evaluation ist optional

        $attempt = $this->attemptService->generateAttempt($user, $exam); // Übergebe Exam statt Module

        // Markiere den Antrag als "erledigt", nur wenn er existiert
        if ($evaluation) {
             $evaluation->update(['status' => 'processed']);
        }

        $secureUrl = route('exams.take', $attempt); // Nutzt RMB

        ActivityLog::create([
            'user_id' => Auth::id(),
            'log_type' => 'EXAM',
            'action' => 'LINK_GENERATED',
            'target_id' => $attempt->id,
            'description' => "Prüfungslink für '{$exam->title}' wurde für {$user->name} generiert." // Angepasster Text
        ]);

        PotentiallyNotifiableActionOccurred::dispatch(
            'Admin\ExamAttemptController@store',
            $user, // Der Prüfling (Empfänger/Trigger)
            $attempt,
            Auth::user() // Der Admin (Akteur)
        );

        return back()->with('success', 'Prüfungslink erfolgreich generiert!')
                     ->with('secure_url', $secureUrl);
    }

    public function show(ExamAttempt $attempt)
    {
        $this->authorize('viewResult', $attempt);
        // Lade die nötigen Relationen (Exam wird benötigt)
        // KORREKTUR: Lade 'evaluator' mit
        $attempt->load(['exam', 'user', 'answers.question.options', 'evaluator']);
        return view('exams.result', compact('attempt'));
    }

    public function update(FinalizeExamRequest $request, ExamAttempt $attempt)
    {
        $this->authorize('setEvaluated', $attempt); // Prüfen ob bewertet werden darf
        $validated = $request->validated();

        // Status für Log/Event ableiten (nicht mehr gespeichert)
        $isPassed = $validated['final_score'] >= $attempt->exam->pass_mark;
        $status_result_for_log = $isPassed ? 'bestanden' : 'nicht_bestanden';
        
        // KORREKTUR: Füge die ID des Admins als Bewerter hinzu
        $validated['evaluator_id'] = Auth::id();

        // KORREKTUR: Übergib alle validierten Daten (inkl. evaluator_id) an den Service
        // (Stelle sicher, dass dein ExamAttemptService 'evaluator_id' verarbeiten kann)
        $attempt = $this->attemptService->finalizeAttempt($attempt, $validated); 

        $actionDesc = "Prüfung '{$attempt->exam->title}' von {$attempt->user->name} wurde als '{$status_result_for_log}' bewertet. Score: {$validated['final_score']}%";
        ActivityLog::create(['user_id' => Auth::id(), 'log_type' => 'EXAM', 'action' => 'EVALUATED', 'target_id' => $attempt->id, 'description' => $actionDesc]);

        PotentiallyNotifiableActionOccurred::dispatch(
            'Admin\ExamAttemptController@update',
            $attempt->user, // Der Prüfling (Empfänger/Trigger)
            $attempt,
            Auth::user(), // Der Admin (Akteur)
            ['status_result' => $status_result_for_log, 'final_score' => $validated['final_score']] // Zusätzliche Daten für Listener
        );

        // Angepasste Erfolgsmeldung
        return redirect()->route('admin.exams.attempts.index')->with('success', "Prüfung finalisiert: Score für {$attempt->user->name} auf {$validated['final_score']}% gesetzt.");
    }

    public function resetAttempt(ExamAttempt $attempt)
    {
        $this->authorize('resetAttempt', $attempt);
        $this->attemptService->resetAttempt($attempt);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'log_type' => 'EXAM',
            'action' => 'RESET',
            'target_id' => $attempt->id,
            'description' => "Prüfungsversuch #{$attempt->id} von {$attempt->user->name} wurde zurückgesetzt."
        ]);

        PotentiallyNotifiableActionOccurred::dispatch(
            'Admin\ExamAttemptController@resetAttempt',
            $attempt->user, // Der Student
            $attempt,
            Auth::user() // Der Admin
        );

        return back()->with('success', 'Prüfungsversuch wurde zurückgesetzt.'); // Optional: Erfolgsmeldung
    }

    public function sendLink(ExamAttempt $attempt) 
    {
        $this->authorize('sendLink', $attempt);
        $secureUrl = route('exams.take', $attempt);

        PotentiallyNotifiableActionOccurred::dispatch(
            'Admin\ExamAttemptController@sendLink',
            $attempt->user, // Der Student
            $attempt,
            Auth::user() // Der Admin
        );

        // ->with('success', ...) hinzugefügt
        return back()->with('success', 'Prüfungslink erneut generiert!')
                     ->with('secure_url', $secureUrl);
    }

    public function setEvaluated(Request $request, ExamAttempt $attempt)
    {
         $this->authorize('setEvaluated', $attempt);
         $validated = $request->validate(['score' => 'required|integer|min:0|max:100']);

         $isPassed = $validated['score'] >= $attempt->exam->pass_mark;
         $resultText = $isPassed ? 'Bestanden' : 'Nicht bestanden';

         $attempt->update([
             'status' => 'evaluated', // Nur Status und Score setzen
             'score' => $validated['score'],
             'evaluator_id' => Auth::id(), // KORREKTUR: Bewerter hier auch setzen
         ]);

         $message = "Prüfungsversuch #{$attempt->id} von {$attempt->user->name} wurde manuell bewertet: Score {$validated['score']}% ({$resultText}).";
         ActivityLog::create([
             'user_id' => Auth::id(),
             'log_type' => 'EXAM',
             'action' => 'EVALUATED_MANUAL', // Eigener Action-Typ?
             'target_id' => $attempt->id,
             'description' => $message
         ]);

         PotentiallyNotifiableActionOccurred::dispatch(
             'Admin\ExamAttemptController@setEvaluated',
             $attempt->user, // Der Student
             $attempt,
             Auth::user(), // Der Admin
             ['isPassed' => $isPassed] // Zusätzliche Info für Listener
         );

         return back()->with('success', 'Score manuell gesetzt.'); // Optional: Erfolgsmeldung
    }

    public function destroy(ExamAttempt $attempt)
    {
        $this->authorize('delete', $attempt);

        // Lade Relationen, bevor das Model gelöscht wird, um Daten für Log/Event zu haben
        $attempt->load(['exam', 'user']);
        $attemptTitle = $attempt->exam->title ?? 'Unbekannte Prüfung';
        $attemptUser = $attempt->user->name ?? 'Unbekannter User';
        $attemptId = $attempt->id;
        $deletedAttemptData = $attempt->toArray(); // Event-Daten sichern

        $this->attemptService->deleteAttempt($attempt);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'log_type' => 'EXAM',
            'action' => 'DELETED',
            'target_id' => $attemptId, // ID verwenden, da Objekt gelöscht ist
            'description' => "Prüfungsversuch #{$attemptId} ('{$attemptTitle}') von {$attemptUser} wurde endgültig gelöscht."
        ]);

        PotentiallyNotifiableActionOccurred::dispatch(
            'Admin\ExamAttemptController@destroy',
             Auth::user(), // Der Admin
             (object) $deletedAttemptData, // Alte Daten
             Auth::user(),
             // Zusätzliche Daten für den Listener
              ['id' => $attemptId, 'exam_title' => $attemptTitle, 'user_name' => $attemptUser]
         );

        return redirect()->route('admin.exams.attempts.index')->with('success', 'Prüfungsversuch wurde endgültig gelöscht.');
    }
}

