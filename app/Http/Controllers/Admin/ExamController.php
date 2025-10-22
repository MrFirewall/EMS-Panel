<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\TrainingModule;
use App\Models\ExamAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\URL;
use App\Events\PotentiallyNotifiableActionOccurred; // Event hinzufügen
use App\Models\User; // Für Typ-Hinting bei Events

class ExamController extends Controller
{
    public function __construct()
    {
        // Policy-Prüfung für Exam CRUD
        $this->authorizeResource(Exam::class, 'exam');

        // Zusätzliche Autorisierungen für Attempt-Methoden (Beispiele)
        $this->middleware('can:viewAny,App\Models\ExamAttempt')->only('attemptsIndex');
        $this->middleware('can:resetAttempt,attempt')->only('resetAttempt');
        $this->middleware('can:setEvaluated,attempt')->only('setEvaluated');
        $this->middleware('can:sendLink,attempt')->only('sendLink');
    }

    // Standard CRUD Methoden für 'exams' (index, create, store, etc.)

    public function index()
    {
        // Zeigt die Liste der Prüfungen (Definiert in der Datenbank)
        $exams = Exam::with('trainingModule')->withCount('questions')->latest()->paginate(15);
        return view('admin.exams.index', compact('exams'));
    }

    public function create()
    {
        // Diese Logik ist korrekt, sie zeigt nur Module an, die noch keine Prüfung haben.
        $modules = TrainingModule::doesntHave('exam')->orderBy('name')->get();
        return view('admin.exams.create', compact('modules'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateExamRequest($request);

        $exam = DB::transaction(function () use ($validated) {
            $exam = Exam::create([
                'training_module_id' => $validated['training_module_id'],
                'title' => $validated['title'],
                'description' => $validated['description'],
                'pass_mark' => $validated['pass_mark'],
            ]);

            foreach ($validated['questions'] as $questionData) {
                $question = $exam->questions()->create([
                    'question_text' => $questionData['question_text'],
                    'type' => $questionData['type'],
                ]);

                if ($questionData['type'] !== 'text_field' && isset($questionData['options'])) {
                    foreach ($questionData['options'] as $oIndex => $optionData) {
                        $isCorrect = false;
                        if ($questionData['type'] === 'single_choice') {
                            $isCorrect = (isset($questionData['correct_option']) && $oIndex == $questionData['correct_option']);
                        } elseif ($questionData['type'] === 'multiple_choice') {
                            $isCorrect = isset($optionData['is_correct']) && $optionData['is_correct'] == '1';
                        }
                        $question->options()->create(['option_text' => $optionData['option_text'], 'is_correct' => $isCorrect]);
                    }
                }
            }
            return $exam;
        });

        ActivityLog::create(['user_id' => Auth::id(), 'log_type' => 'EXAM', 'action' => 'CREATED', 'target_id' => $exam->id, 'description' => "Prüfung '{$exam->title}' wurde erstellt."]);

        // --- BENACHRICHTIGUNG VIA EVENT ---
        PotentiallyNotifiableActionOccurred::dispatch(
            'Admin\ExamController@store',
            Auth::user(), // Der Ersteller
            $exam,        // Die erstellte Prüfung
            Auth::user()  // Der Akteur
        );
        // ---------------------------------

        return redirect()->route('admin.exams.index'); // Ohne success
    }

    public function show(Exam $exam)
    {
        $exam->load('trainingModule', 'questions.options');
        return view('admin.exams.show', compact('exam'));
    }

    public function edit(Exam $exam)
    {
        $exam->load('questions.options');

        $modules = TrainingModule::whereDoesntHave('exam')
                                  ->orWhere('id', $exam->training_module_id)
                                  ->orderBy('name')
                                  ->get();

        $initialData = old('questions');

        if (!$initialData) {
            $initialData = $exam->questions->map(function ($q) {
                $data = [
                    'id' => $q->id,
                    'question_text' => $q->question_text,
                    'type' => $q->type,
                    'options' => $q->options->map(function($o) {
                        return [
                            'id' => $o->id,
                            'option_text' => $o->option_text,
                            'is_correct' => (bool)$o->is_correct
                        ];
                    })->all()
                ];

                if ($q->type === 'single_choice') {
                    $correctIndex = $q->options->search(function($o) {
                        return $o->is_correct;
                    });
                    $data['correct_option'] = $correctIndex !== false ? $correctIndex : null;
                }
                return $data;
            })->all();
        }

        $questionsJson = json_encode($initialData ?? []);

        return view('admin.exams.edit', compact('exam', 'modules', 'questionsJson'));
    }

    public function update(Request $request, Exam $exam)
    {
        $validated = $this->validateExamRequest($request, $exam);

        DB::transaction(function () use ($validated, $exam) {
            $exam->update([
                'training_module_id' => $validated['training_module_id'],
                'title' => $validated['title'],
                'description' => $validated['description'],
                'pass_mark' => $validated['pass_mark'],
            ]);

            $submittedQuestionIds = [];
            foreach ($validated['questions'] as $questionData) {
                $question = $exam->questions()->updateOrCreate(
                    ['id' => $questionData['id'] ?? null],
                    ['question_text' => $questionData['question_text'], 'type' => $questionData['type']]
                );
                $submittedQuestionIds[] = $question->id;

                if ($questionData['type'] !== 'text_field' && isset($questionData['options'])) {
                    $submittedOptionIds = [];
                    foreach ($questionData['options'] as $oIndex => $optionData) {
                        $isCorrect = false;
                        if ($questionData['type'] === 'single_choice') {
                            $isCorrect = (isset($questionData['correct_option']) && $oIndex == $questionData['correct_option']);
                        } elseif ($questionData['type'] === 'multiple_choice') {
                            $isCorrect = isset($optionData['is_correct']) && $optionData['is_correct'] == '1';
                        }
                        $option = $question->options()->updateOrCreate(
                            ['id' => $optionData['id'] ?? null],
                            ['option_text' => $optionData['option_text'], 'is_correct' => $isCorrect]
                        );
                        $submittedOptionIds[] = $option->id;
                    }
                    $question->options()->whereNotIn('id', $submittedOptionIds)->delete();
                } else {
                    $question->options()->delete();
                }
            }
            $exam->questions()->whereNotIn('id', $submittedQuestionIds)->delete();
        });

        ActivityLog::create(['user_id' => Auth::id(), 'log_type' => 'EXAM', 'action' => 'UPDATED', 'target_id' => $exam->id, 'description' => "Prüfung '{$exam->title}' wurde aktualisiert."]);

        // --- BENACHRICHTIGUNG VIA EVENT ---
        PotentiallyNotifiableActionOccurred::dispatch(
            'Admin\ExamController@update',
            Auth::user(), // Der Bearbeiter
            $exam,        // Die aktualisierte Prüfung
            Auth::user()  // Der Akteur
        );
        // ---------------------------------

        return redirect()->route('admin.exams.index'); // Ohne success
    }

    public function destroy(Exam $exam)
    {
        $examTitle = $exam->title;
        $examId = $exam->id;
        $deletedExamData = $exam->toArray(); // Kopie für Event
        $exam->delete();

        ActivityLog::create(['user_id' => Auth::id(), 'log_type' => 'EXAM', 'action' => 'DELETED', 'target_id' => $examId, 'description' => "Prüfung '{$examTitle}' wurde gelöscht."]);

        // --- BENACHRICHTIGUNG VIA EVENT ---
        PotentiallyNotifiableActionOccurred::dispatch(
            'Admin\ExamController@destroy',
            Auth::user(),               // Der Löschende
            (object) $deletedExamData, // Übergabe als Objekt
            Auth::user()                // Der Akteur
        );
        // ---------------------------------

        return redirect()->route('admin.exams.index'); // Ohne success
    }

    // =========================================================
    // METHODEN FÜR EXAM ATTEMPTS
    // =========================================================

    public function attemptsIndex()
    {
        $attempts = ExamAttempt::with(['exam.trainingModule', 'user'])
                                ->orderBy('updated_at', 'desc')
                                ->paginate(25);
        return view('admin.exams.attempts-index', compact('attempts'));
    }

    public function resetAttempt(ExamAttempt $attempt)
    {
        DB::transaction(function () use ($attempt) {
            $attempt->answers()->delete();
            $attempt->update([
                'status' => 'in_progress',
                'completed_at' => null,
                'score' => null,
                'flags' => null,
            ]);

            ActivityLog::create(['user_id' => Auth::id(), 'log_type' => 'EXAM', 'action' => 'RESET', 'target_id' => $attempt->id, 'description' => "Prüfungsversuch #{$attempt->id} von {$attempt->user->name} wurde zurückgesetzt."]);
        });

        // --- BENACHRICHTIGUNG VIA EVENT ---
        PotentiallyNotifiableActionOccurred::dispatch(
            'Admin\ExamController@resetAttempt',
            $attempt->user, // Der betroffene User
            $attempt,       // Der Prüfungsversuch
            Auth::user()    // Der Admin, der zurückgesetzt hat
        );
        // ---------------------------------

        return back(); // Ohne success
    }

    public function setEvaluated(Request $request, ExamAttempt $attempt)
    {
        $validated = $request->validate([
            'score' => 'required|integer|min:0|max:100',
        ]);

        // KORREKTUR: Status auf 'evaluated' setzen, egal ob bestanden oder nicht, da manuell bewertet
        $status = 'evaluated';
        $isPassed = $validated['score'] >= $attempt->exam->pass_mark;
        $resultText = $isPassed ? 'Bestanden' : 'Nicht bestanden';

        $attempt->update([
            'status' => $status,
            'score' => $validated['score'],
        ]);

        $message = "Prüfungsversuch #{$attempt->id} von {$attempt->user->name} wurde manuell bewertet: {$resultText} ({$validated['score']}%).";
        ActivityLog::create(['user_id' => Auth::id(), 'log_type' => 'EXAM', 'action' => 'EVALUATED', 'target_id' => $attempt->id, 'description' => $message]);

        // --- BENACHRICHTIGUNG VIA EVENT ---
        PotentiallyNotifiableActionOccurred::dispatch(
            'Admin\ExamController@setEvaluated',
            $attempt->user, // Der betroffene User
            $attempt,       // Der Prüfungsversuch
            Auth::user()    // Der Admin, der bewertet hat
        );
        // ---------------------------------

        return back(); // Ohne success
    }

    public function sendLink(ExamAttempt $attempt)
    {
        $secureUrl = route('exams.take', ['uuid' => $attempt->uuid]);

        // --- BENACHRICHTIGUNG VIA EVENT ---
        // Hier könnte man den Admin benachrichtigen, dass der Link neu generiert wurde,
        // oder direkt den Prüfling (je nach Regelwerk)
        PotentiallyNotifiableActionOccurred::dispatch(
            'Admin\ExamController@sendLink',
            $attempt->user, // Der betroffene User
            $attempt,       // Der Prüfungsversuch
            Auth::user()    // Der Admin, der den Link generiert hat
        );
        // ---------------------------------

        // Gib die URL zurück, damit der Admin sie kopieren kann.
        return back()->with('secure_url', $secureUrl); // Ohne success-Nachricht, nur die URL
    }

    // =========================================================
    // VALIDIERUNG
    // =========================================================

    private function validateExamRequest(Request $request, ?Exam $exam = null): array
    {
        $moduleIdRule = 'required|exists:training_modules,id';
        $moduleIdRule .= $exam ? '|unique:exams,training_module_id,' . $exam->id : '|unique:exams,training_module_id';

        $baseRules = [
            'training_module_id' => $moduleIdRule,
            'title' => 'required|string|max:255',
            'pass_mark' => 'required|integer|min:1|max:100',
            'description' => 'nullable|string',
            'questions' => 'required|array|min:1',
            'questions.*.id' => 'nullable|integer|exists:questions,id',
            'questions.*.question_text' => 'required|string',
            'questions.*.type' => 'required|in:single_choice,multiple_choice,text_field',
            'questions.*.options' => 'nullable|array',
            'questions.*.options.*.id' => 'nullable',
            'questions.*.options.*.option_text' => 'nullable',
            'questions.*.options.*.is_correct' => 'nullable',
            'questions.*.correct_option' => 'nullable',
        ];

        $validator = Validator::make($request->all(), $baseRules);

        $validator->after(function ($validator) use ($request) {
            foreach ($request->input('questions', []) as $key => $question) {
                $type = $question['type'] ?? 'single_choice';
                if ($type === 'text_field') continue;

                if (!isset($question['options']) || !is_array($question['options']) || count($question['options']) < 2) {
                    $validator->errors()->add("questions.{$key}.options", "Für eine Auswahlfrage werden mindestens 2 Antwortmöglichkeiten benötigt.");
                    continue;
                }

                foreach($question['options'] as $optKey => $option) {
                    if(empty($option['option_text'])) {
                        $validator->errors()->add("questions.{$key}.options.{$optKey}.option_text", "Der Antworttext darf nicht leer sein.");
                    }
                }

                if ($type === 'single_choice') {
                    if (!isset($question['correct_option'])) {
                        $validator->errors()->add("questions.{$key}.correct_option", "Für eine Einzelantwort-Frage muss eine korrekte Antwort markiert sein.");
                    }
                } elseif ($type === 'multiple_choice') {
                    $hasCorrect = collect($question['options'])->contains(fn ($opt) => isset($opt['is_correct']) && $opt['is_correct'] == '1');
                    if (!$hasCorrect) {
                        $validator->errors()->add("questions.{$key}.options", "Für eine Mehrfachantwort-Frage muss mindestens eine korrekte Antwort markiert sein.");
                    }
                }
            }
        });

        return $validator->validate();
    }
}
