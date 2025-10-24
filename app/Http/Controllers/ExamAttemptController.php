<?php

namespace App\Http\Controllers;

use App\Models\ExamAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ActivityLog;
use App\Events\PotentiallyNotifiableActionOccurred;
use App\Services\ExamAttemptService; // NEU
use App\Http\Requests\SubmitExamRequest; // NEU

class ExamAttemptController extends Controller
{
    protected $attemptService;

    public function __construct(ExamAttemptService $attemptService)
    {
        $this->attemptService = $attemptService;
    }

    /**
     * Zeigt die Prüfungsseite an (ehemals 'take').
     * Route Model Binding (RMB) via UUID funktioniert dank Model-Definition.
     */
    public function show(ExamAttempt $attempt)
    {
        // Policy prüft, ob der eingeloggte User der Besitzer des Versuchs ist.
        $this->authorize('take', $attempt);

        if ($attempt->status !== 'in_progress') {
            return redirect()->route('exams.submitted')->with('info', 'Diese Prüfung wurde bereits abgeschlossen.');
        }

        $attempt->load('exam.questions.options');
        return view('exams.take', compact('attempt'));
    }

    /**
     * Verarbeitet die Einreichung der Prüfung (ehemals 'submit').
     * RMB via UUID.
     */
    public function update(SubmitExamRequest $request, ExamAttempt $attempt)
    {
        // Autorisierung (submit) geschieht bereits im Form Request
        
        $attempt = $this->attemptService->submitAttempt(
            $attempt,
            $request->validated()['answers']
        );

        // --- ACTIVITY LOG ---
        ActivityLog::create([
            'user_id' => Auth::id(),
            'log_type' => 'EXAM',
            'action' => 'SUBMITTED',
            'target_id' => $attempt->id,
            'description' => "Prüfung '{$attempt->exam->title}' wurde von {$attempt->user->name} eingereicht."
        ]);

        // --- BENACHRICHTIGUNG VIA EVENT (an Admins/Prüfer) ---
        PotentiallyNotifiableActionOccurred::dispatch(
            'ExamAttemptController@update', // (ehemals submit)
            Auth::user(), // Der Prüfling (Auslöser)
            $attempt,     // Das zugehörige Modell
            Auth::user()   // Der Prüfling (Akteur)
        );

        // Leitet auf die generische Bestätigungsseite um
        return redirect()->route('exams.submitted');
    }
    
    /**
     * Zeigt die generische "Eingereicht"-Seite.
     */
    public function submitted()
    {
        return view('exams.submitted');
    }
}
