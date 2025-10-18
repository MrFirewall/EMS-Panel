<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Evaluation;
use App\Models\ExamAttempt;
use App\Models\Option;
use App\Models\TrainingModule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ExamController extends Controller
{
    /**
     * Generiert einen neuen, einmaligen Prüfungsversuch und einen sicheren Link dazu.
     * Nur für Ausbilder/Admins.
     */
    public function generateLink(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'module_id' => 'required|exists:training_modules,id',
            'evaluation_id' => 'required|exists:evaluations,id',
        ]);

        $module = TrainingModule::find($validated['module_id']);
        $user = User::find($validated['user_id']);
        $evaluation = Evaluation::find($validated['evaluation_id']);

        if (!$module->exam) {
            return back()->with('error', 'Für dieses Modul ist keine Prüfung hinterlegt.');
        }

        $this->authorize('generateExamLink', ExamAttempt::class);

        // Erstelle einen neuen Prüfungsversuch
        $attempt = ExamAttempt::create([
            'exam_id' => $module->exam->id,
            'user_id' => $user->id,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        // Markiere den Antrag als "erledigt"
        $evaluation->update(['status' => 'processed']);

        // Generiere die sichere URL
        $secureUrl = route('exams.take', ['uuid' => $attempt->uuid]);

        // Optional: Sende Benachrichtigung an den Prüfer/Admin
        // Notification::send(Auth::user(), new ExamLinkGeneratedNotification($user, $secureUrl));

        return back()->with('success', 'Prüfungslink erfolgreich generiert! Senden Sie diesen Link an den Prüfling:')
                     ->with('secure_url', $secureUrl);
    }

    /**
     * Zeigt die Prüfungsseite an, die über die sichere UUID aufgerufen wird.
     */
    public function take(string $uuid)
    {
        $attempt = ExamAttempt::where('uuid', $uuid)->firstOrFail();
        
        // Stellt sicher, dass nur der zugewiesene User den Test machen kann.
        if (Auth::id() !== $attempt->user_id) {
            abort(403, 'Sie sind nicht berechtigt, diese Prüfung abzulegen.');
        }
        
        if ($attempt->status !== 'in_progress') {
             return redirect()->route('exams.result', $attempt->uuid)->with('info', 'Diese Prüfung wurde bereits abgeschlossen.');
        }

        $attempt->load('exam.questions.options');
        return view('exams.take', compact('attempt'));
    }

    /**
     * Nimmt die Prüfungsantworten entgegen und wertet sie aus.
     */
    public function submit(Request $request, string $uuid)
    {
        $attempt = ExamAttempt::where('uuid', $uuid)->firstOrFail();
        $this->authorize('submit', $attempt);

        $validated = $request->validate(['answers' => 'required|array']);
        $answers = $validated['answers'];
        $totalQuestions = $attempt->exam->questions()->count();
        $correctAnswers = 0;

        DB::transaction(function () use ($answers, $attempt, &$correctAnswers, $totalQuestions) {
            foreach ($answers as $questionId => $optionId) {
                $option = Option::find($optionId);
                $isCorrect = $option && $option->is_correct;
                if ($isCorrect) $correctAnswers++;

                $attempt->answers()->create([
                    'question_id' => $questionId, 'option_id' => $optionId, 'is_correct_at_time_of_answer' => $isCorrect,
                ]);
            }

            $score = ($totalQuestions > 0) ? round(($correctAnswers / $totalQuestions) * 100) : 0;
            $attempt->update(['completed_at' => now(), 'status' => 'submitted', 'score' => $score]);
        });

        return redirect()->route('exams.result', $attempt->uuid);
    }

    /**
     * Zeigt die Ergebnisseite nach Abschluss an.
     */
    public function result(string $uuid)
    {
        $attempt = ExamAttempt::where('uuid', $uuid)->firstOrFail();
        $this->authorize('viewResult', $attempt);
        return view('exams.result', compact('attempt'));
    }

    /**
     * API-Endpunkt für die Anti-Betrugs-Funktion.
     */
    public function flag(Request $request, string $uuid)
    {
        $attempt = ExamAttempt::where('uuid', $uuid)->firstOrFail();
        // Leichte Autorisierung: Nur der User selbst kann seine Prüfung flaggen
        if (Auth::id() !== $attempt->user_id || $attempt->status !== 'in_progress') {
            return response()->json(['status' => 'error'], 403);
        }

        $flags = $attempt->flags ?? [];
        $flags[] = ['timestamp' => now()->toDateTimeString(), 'event' => 'User lost focus on the page'];
        $attempt->update(['flags' => $flags]);
        
        // Hier könnte man eine Echtzeit-Benachrichtigung an einen Prüfer auslösen

        return response()->json(['status' => 'flagged']);
    }
}