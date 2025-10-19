<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\TrainingModule;
use App\Models\ExamAttempt; // HINZUGEFÜGT
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ExamController extends Controller
{
    public function __construct()
    {
        // Policy-Prüfung für Exam CRUD
        $this->authorizeResource(Exam::class, 'exam');
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

                if ($questionData['type'] !== 'text_field') {
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
        return redirect()->route('admin.exams.index')->with('success', 'Prüfung erfolgreich erstellt!');
    }

    public function show(Exam $exam)
    {
        $exam->load('trainingModule', 'questions.options');
        return view('admin.exams.show', compact('exam'));
    }

    /**
     * KORRIGIERTE 'edit' METHODE
     * Bereitet die Daten für die View vor, um Blade-Parse-Fehler zu vermeiden.
     */
    public function edit(Exam $exam)
    {
        $exam->load('questions.options');
        
        // 1. Lade nur Module, die keine Prüfung haben, ODER das dieser Prüfung zugewiesene Modul
        $modules = TrainingModule::whereDoesntHave('exam')
                                   ->orWhere('id', $exam->training_module_id)
                                   ->orderBy('name')
                                   ->get();

        // 2. Prüfe auf 'old input' (nach Validierungsfehler)
        $initialData = old('questions');

        // 3. Wenn kein 'old input' vorhanden ist, transformiere die Datenbankdaten
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
                            'is_correct' => (bool)$o->is_correct // Sorge für echtes true/false
                        ];
                    })->all()
                ];
                
                // Für Single-Choice den korrekten Index finden, den JS benötigt
                if ($q->type === 'single_choice') {
                    $correctIndex = $q->options->search(function($o) { 
                        return $o->is_correct; 
                    });
                    $data['correct_option'] = $correctIndex !== false ? $correctIndex : null; 
                }
                return $data;
            })->all();
        }

        // 4. Wandle die Daten in einen sauberen JSON-String um
        $questionsJson = json_encode($initialData ?? []);

        // 5. Übergib die Variablen an die View
        return view('admin.exams.edit', compact('exam', 'modules', 'questionsJson'));
    }

    /**
     * Aktualisiert eine bestehende Prüfung mit robusterer Logik.
     */
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

                // KORRIGIERT: Prüfe, ob 'options' überhaupt existiert, bevor die Schleife gestartet wird.
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
                    // Wenn Typ 'text_field' ist oder keine Optionen gesendet wurden, alle alten Optionen löschen
                    $question->options()->delete();
                }
            }
            // Lösche Fragen, die nicht mehr im Formular vorhanden waren
            $exam->questions()->whereNotIn('id', $submittedQuestionIds)->delete();
        });

        ActivityLog::create(['user_id' => Auth::id(), 'log_type' => 'EXAM', 'action' => 'UPDATED', 'target_id' => $exam->id, 'description' => "Prüfung '{$exam->title}' wurde aktualisiert."]);
        return redirect()->route('admin.exams.index')->with('success', 'Prüfung erfolgreich aktualisiert!');
    }

    public function destroy(Exam $exam)
    {
        $examTitle = $exam->title;
        $examId = $exam->id;
        $exam->delete();

        ActivityLog::create(['user_id' => Auth::id(), 'log_type' => 'EXAM', 'action' => 'DELETED', 'target_id' => $examId, 'description' => "Prüfung '{$examTitle}' wurde gelöscht."]);
        return redirect()->route('admin.exams.index')->with('success', 'Prüfung erfolgreich gelöscht.');
    }

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
            
            // --- KORREKTUR: DIESE ZEILEN HINZUFÜGEN ---
            // Erlaube diese Felder, damit sie 'validate()' passieren.
            // Die 'after()'-Methode prüft dann die Details.
            'questions.*.options' => 'nullable|array',
            'questions.*.options.*.id' => 'nullable',
            'questions.*.options.*.option_text' => 'nullable',
            'questions.*.options.*.is_correct' => 'nullable',
            'questions.*.correct_option' => 'nullable',
            // --- ENDE DER KORREKTUR ---
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
                
                // (Der Rest Ihrer 'after'-Logik ist korrekt)
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
    
    // NEUE METHODEN FÜR EXAM ATTEMPTS START
    
    /**
     * Zeigt eine Übersicht aller Prüfungsversuche (ExamAttempts)
     * zur Verwaltung an (zum Nachverfolgen und Bewerten).
     */
    public function attemptsIndex()
    {
        // Lädt alle Versuche und die zugehörigen Prüfungs- und Benutzerdaten.
        // Sortiert nach dem letzten Update, um die aktuellsten Versuche oben zu sehen.
        $attempts = ExamAttempt::with(['exam.trainingModule', 'user'])
                              ->orderBy('updated_at', 'desc')
                              ->paginate(25); // Paginierung für große Datenmengen

        // WICHTIG: Erstellen Sie eine neue View namens 'admin.exams.attempts-index'
        return view('admin.exams.attempts-index', compact('attempts'));
    }

    /**
     * Setzt einen Prüfungsversuch auf "in_progress" zurück.
     * Nützlich für Admin-Korrekturen oder Neustarts.
     */
    public function resetAttempt(ExamAttempt $attempt)
    {
        // Autorisation: Sicherstellen, dass nur Admins diese Aktion durchführen können
        // Sie benötigen diese Fähigkeit in Ihrer Policy/Gate.
        $this->authorize('resetAttempt', $attempt); 
        
        // Fügt DB-Use hinzu (falls nicht bereits global in der Klasse verfügbar)
        if (!class_exists('DB')) {
            throw new \Exception("DB Facade not found. Please add 'use Illuminate\Support\Facades\DB;' to the top of the file.");
        }


        // Die Transaktion zurücksetzen (Löscht alte Antworten und setzt den Status zurück)
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

        return back()->with('success', 'Prüfungsversuch erfolgreich zurückgesetzt. Der Link ist wieder aktiv.');
    }
    
    // NEUE METHODEN FÜR EXAM ATTEMPTS ENDE
}
