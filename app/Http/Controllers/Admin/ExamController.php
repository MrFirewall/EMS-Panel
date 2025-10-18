<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\TrainingModule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ExamController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Exam::class, 'exam');
    }

    public function index()
    {
        $exams = Exam::with('trainingModule')->withCount('questions')->latest()->paginate(15);
        return view('admin.exams.index', compact('exams'));
    }

    public function create()
    {
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

    public function edit(Exam $exam)
    {
        $exam->load('questions.options');
        $modules = TrainingModule::orderBy('name')->get();
        return view('admin.exams.edit', compact('exam', 'modules'));
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
                    $question->options()->delete();
                }
            }
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

