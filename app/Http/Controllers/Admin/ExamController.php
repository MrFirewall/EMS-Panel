<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Question;
use App\Models\Option;
use App\Models\TrainingModule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ExamController extends Controller
{
    public function __construct()
    {
        // Wendet die ExamPolicy auf alle Standard-Ressourcenmethoden an
        $this->authorizeResource(Exam::class, 'exam');
    }

    /**
     * Zeigt eine Liste aller Prüfungen an.
     */
    public function index()
    {
        $exams = Exam::with('trainingModule')->latest()->paginate(15);
        return view('admin.exams.index', compact('exams'));
    }

    /**
     * Zeigt das Formular zum Erstellen einer neuen Prüfung an.
     */
    public function create()
    {
        $modules = TrainingModule::doesntHave('exam')->orderBy('name')->get();
        return view('admin.exams.create', compact('modules'));
    }

    /**
     * Speichert eine neue Prüfung.
     */
    public function store(Request $request)
    {
        // Validierung bleibt gleich wie zuvor
        $validated = $request->validate([
            'training_module_id' => 'required|exists:training_modules,id|unique:exams,training_module_id',
            'title' => 'required|string|max:255',
            'pass_mark' => 'required|integer|min:1|max:100',
            'description' => 'nullable|string',
            'questions' => 'required|array|min:1',
            'questions.*.question_text' => 'required|string',
            'questions.*.options' => 'required|array|min:2',
            'questions.*.options.*.option_text' => 'required|string',
            'questions.*.correct_option' => 'required|integer',
        ]);

        $exam = DB::transaction(function () use ($validated) {
            $exam = Exam::create([
                'training_module_id' => $validated['training_module_id'],
                'title' => $validated['title'],
                'description' => $validated['description'],
                'pass_mark' => $validated['pass_mark'],
            ]);

            foreach ($validated['questions'] as $qIndex => $questionData) {
                $question = $exam->questions()->create(['question_text' => $questionData['question_text']]);

                foreach ($questionData['options'] as $oIndex => $optionData) {
                    $question->options()->create([
                        'option_text' => $optionData['option_text'],
                        'is_correct' => ($oIndex == $questionData['correct_option']),
                    ]);
                }
            }
            return $exam;
        });
        
        ActivityLog::create([
            'user_id' => Auth::id(), 'log_type' => 'EXAM', 'action' => 'CREATED', 'target_id' => $exam->id,
            'description' => "Prüfung '{$exam->title}' wurde erstellt.",
        ]);

        return redirect()->route('admin.exams.index')->with('success', 'Prüfung erfolgreich erstellt!');
    }

    /**
     * Zeigt die Detailansicht einer Prüfung.
     */
    public function show(Exam $exam)
    {
        $exam->load('trainingModule', 'questions.options');
        return view('admin.exams.show', compact('exam'));
    }

    /**
     * Zeigt das Formular zum Bearbeiten einer Prüfung.
     */
    public function edit(Exam $exam)
    {
        $exam->load('questions.options');
        $modules = TrainingModule::orderBy('name')->get(); // Alle Module für den Fall einer Änderung laden
        return view('admin.exams.edit', compact('exam', 'modules'));
    }

    /**
     * Aktualisiert eine bestehende Prüfung.
     */
    public function update(Request $request, Exam $exam)
    {
        $validated = $request->validate([
            'training_module_id' => 'required|exists:training_modules,id|unique:exams,training_module_id,' . $exam->id,
            'title' => 'required|string|max:255',
            'pass_mark' => 'required|integer|min:1|max:100',
            'description' => 'nullable|string',
            'questions' => 'required|array|min:1',
            'questions.*.id' => 'nullable|integer|exists:questions,id', // ID für bestehende Fragen
            'questions.*.question_text' => 'required|string',
            'questions.*.options' => 'required|array|min:2',
            'questions.*.options.*.id' => 'nullable|integer|exists:options,id', // ID für bestehende Optionen
            'questions.*.options.*.option_text' => 'required|string',
            'questions.*.correct_option' => 'required|integer',
        ]);

        DB::transaction(function () use ($validated, $exam) {
            // 1. Prüfungsdetails aktualisieren
            $exam->update([
                'training_module_id' => $validated['training_module_id'],
                'title' => $validated['title'],
                'description' => $validated['description'],
                'pass_mark' => $validated['pass_mark'],
            ]);

            $submittedQuestionIds = [];
            foreach ($validated['questions'] as $qIndex => $questionData) {
                // Frage aktualisieren oder erstellen
                $question = $exam->questions()->updateOrCreate(
                    ['id' => $questionData['id'] ?? null],
                    ['question_text' => $questionData['question_text']]
                );
                $submittedQuestionIds[] = $question->id;

                $submittedOptionIds = [];
                foreach ($questionData['options'] as $oIndex => $optionData) {
                    // Option aktualisieren oder erstellen
                    $option = $question->options()->updateOrCreate(
                        ['id' => $optionData['id'] ?? null],
                        [
                            'option_text' => $optionData['option_text'],
                            'is_correct' => ($oIndex == $questionData['correct_option']),
                        ]
                    );
                    $submittedOptionIds[] = $option->id;
                }
                // Veraltete Optionen für diese Frage löschen
                $question->options()->whereNotIn('id', $submittedOptionIds)->delete();
            }
            // Veraltete Fragen für diese Prüfung löschen
            $exam->questions()->whereNotIn('id', $submittedQuestionIds)->delete();
        });

        ActivityLog::create([
            'user_id' => Auth::id(), 'log_type' => 'EXAM', 'action' => 'UPDATED', 'target_id' => $exam->id,
            'description' => "Prüfung '{$exam->title}' wurde aktualisiert.",
        ]);

        return redirect()->route('admin.exams.index')->with('success', 'Prüfung erfolgreich aktualisiert!');
    }

    /**
     * Löscht eine Prüfung.
     */
    public function destroy(Exam $exam)
    {
        $examTitle = $exam->title;
        $examId = $exam->id;
        $exam->delete();

        ActivityLog::create([
            'user_id' => Auth::id(), 'log_type' => 'EXAM', 'action' => 'DELETED', 'target_id' => $examId,
            'description' => "Prüfung '{$examTitle}' wurde gelöscht.",
        ]);

        return redirect()->route('admin.exams.index')->with('success', 'Prüfung erfolgreich gelöscht.');
    }
}

