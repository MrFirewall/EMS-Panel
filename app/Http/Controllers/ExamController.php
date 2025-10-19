<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Evaluation;
use App\Models\ExamAttempt;
use App\Models\Option;
use App\Models\TrainingModule;
use App\Models\User;
use App\Models\TrainingModuleUser; // HINZUGEFÜGT
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
             // PRÜFUNG IST ABGESCHLOSSEN, WEITERLEITUNG AUF DIE GENERISCHE BESTÄTIGUNGSSEITE
             return redirect()->route('exams.submitted')->with('info', 'Diese Prüfung wurde bereits abgeschlossen und zur Bewertung eingereicht.');
        }

        $attempt->load('exam.questions.options');
        return view('exams.take', compact('attempt'));
    }

    /**
     * Handles the submission of an exam.
     */
    public function submit(Request $request, $uuid)
    {
        $attempt = ExamAttempt::where('uuid', $uuid)->firstOrFail();

        // Use the policy to authorize the action
        $this->authorize('submit', $attempt);

        $answers = $request->input('answers', []);
        $correctAnswers = 0;
        
        // Eager load questions and options to perform fewer database queries
        $questions = $attempt->exam->questions()->with('options')->get()->keyBy('id');

        DB::transaction(function () use ($answers, $attempt, &$correctAnswers, $questions) {
            foreach ($answers as $questionId => $submittedAnswer) {
                $question = $questions->get($questionId);
                if (!$question) continue; // Skip if a submitted answer doesn't match a question

                switch ($question->type) {
                    case 'single_choice':
                        $option = $question->options->find($submittedAnswer);
                        $isCorrect = $option && $option->is_correct;
                        if ($isCorrect) {
                            $correctAnswers++;
                        }

                        $attempt->answers()->create([
                            'question_id' => $questionId,
                            'option_id' => $submittedAnswer,
                            'is_correct_at_time_of_answer' => $isCorrect,
                        ]);
                        break;

                    case 'multiple_choice':
                        // Ensure submittedAnswer is an array for safety
                        $submittedAnswerIds = collect(is_array($submittedAnswer) ? $submittedAnswer : []);

                        // Get the IDs of all correct options for this question
                        $correctOptionIds = $question->options->where('is_correct', true)->pluck('id');

                        // The answer is correct if the submitted IDs match the correct IDs exactly
                        $isCorrect = $submittedAnswerIds->sort()->values()->all() == $correctOptionIds->sort()->values()->all(); 
                        if ($isCorrect) {
                            $correctAnswers++;
                        }

                        // Save each submitted option individually for review
                        foreach ($submittedAnswerIds as $optionId) {
                            // HINZUGEFÜGTE LOGIK: Prüfen Sie die Korrektheit der spezifischen Option
                            $option = $question->options->firstWhere('id', $optionId);
                            $isOptionCorrect = $option && $option->is_correct;
                            
                            $attempt->answers()->create([
                                'question_id' => $questionId,
                                'option_id' => $optionId,
                                'is_correct_at_time_of_answer' => $isOptionCorrect, 
                            ]);
                        }
                        break;

                    case 'text_field':
                        // Text answers are not automatically scored
                        $attempt->answers()->create([
                            'question_id' => $questionId,
                            'option_id' => null,
                            'text_answer' => $submittedAnswer,
                            'is_correct_at_time_of_answer' => 0, 
                        ]);
                        break;
                }
            }

            // Calculate score based only on questions that can be auto-graded
            $scorableQuestionsCount = $questions->whereIn('type', ['single_choice', 'multiple_choice'])->count();
            $score = ($scorableQuestionsCount > 0) ? round(($correctAnswers / $scorableQuestionsCount) * 100) : 0;
            
            $attempt->update([
                'completed_at' => now(),
                'status' => 'submitted',
                'score' => $score,
            ]);
        });
        
        // ZIEL 1: Leitet den Prüfling auf die generische Bestätigungsseite um
        return redirect()->route('exams.submitted');
    }

    /**
     * ZEIGT die Ergebnisseite an - NUR FÜR TRAINER/ADMINS (Ziel 2)
     */
    public function result(string $uuid)
    {
        $attempt = ExamAttempt::where('uuid', $uuid)->firstOrFail();
        // Stellt sicher, dass NUR Admins oder der Besitzer die Ergebnisseite sehen
        $this->authorize('viewResult', $attempt); 
        
        // Wenn der User kein Admin ist, leiten wir ihn trotzdem auf die generische Seite um.
        if (Auth::id() !== $attempt->user_id && !$attempt->user->can('evaluations.view.all')) {
            return redirect()->route('exams.submitted')->with('error', 'Die Ergebnisseite ist nur für Prüfer zugänglich.');
        }

        return view('exams.result', compact('attempt'));
    }
    
    /**
     * NEU: Schließt die Bewertung ab und setzt den Modulstatus (Ziel 3)
     */
    public function finalizeEvaluation(Request $request, string $uuid)
    {
        $attempt = ExamAttempt::where('uuid', $uuid)->firstOrFail();
        $this->authorize('finalizeEvaluation', $attempt); 

        $validated = $request->validate([
            'final_score' => 'required|integer|min:0|max:100',
            'status_result' => 'required|in:bestanden,nicht_bestanden',
        ]);
        
        $isPassed = $validated['status_result'] === 'bestanden';

        DB::transaction(function () use ($attempt, $validated, $isPassed) {
            // 1. Prüfungsversuch auf finalen Status setzen
            $attempt->update([
                'score' => $validated['final_score'],
                'status' => 'evaluated',
            ]);

            // 2. Zugehöriges Trainingsmodul-User-Mapping aktualisieren
            $moduleUser = TrainingModuleUser::where('user_id', $attempt->user_id)
                                            ->where('training_module_id', $attempt->exam->training_module_id)
                                            ->first();

            if ($moduleUser) {
                $moduleUser->update([
                    'status' => $validated['status_result'],
                    'completed_at' => now()->toDateString(),
                    'notes' => 'Abgeschlossen durch Prüfung: ' . $attempt->exam->title,
                ]);
            }
            
            // Logeintrag (optional, aber gut)
            $actionDesc = "Prüfung '{$attempt->exam->title}' von {$attempt->user->name} wurde als '{$validated['status_result']}' bewertet. Score: {$validated['final_score']}%";
            // ActivityLog::create(['user_id' => Auth::id(), 'log_type' => 'EXAM', 'action' => 'EVALUATED', 'target_id' => $attempt->id, 'description' => $actionDesc]);
        });

        return redirect()->route('admin.exams.attempts.index')->with('success', "Prüfung finalisiert: Status für {$attempt->user->name} auf '{$validated['status_result']}' gesetzt.");
    }
}
