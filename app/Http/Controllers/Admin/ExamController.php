<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
// use App\Models\TrainingModule; // Nicht mehr benötigt
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use App\Events\PotentiallyNotifiableActionOccurred;
use App\Services\ExamService;
use App\Http\Requests\Admin\StoreExamRequest;
use App\Http\Requests\Admin\UpdateExamRequest;

class ExamController extends Controller
{
    protected $examService;

    public function __construct(ExamService $examService)
    {
        $this->examService = $examService;
        // Policy-Prüfung für Exam CRUD
        $this->authorizeResource(Exam::class, 'exam');
    }

    public function index()
    {
        $exams = Exam::withCount('questions')->latest()->paginate(15); // Ohne trainingModule
        return view('admin.exams.index', compact('exams'));
    }

    public function create()
    {
        // Keine Module mehr nötig
        return view('admin.exams.create'); // Ohne compact('modules')
    }

    public function store(StoreExamRequest $request)
    {
        $exam = $this->examService->createExam($request->validated());

        ActivityLog::create(['user_id' => Auth::id(), 'log_type' => 'EXAM', 'action' => 'CREATED', 'target_id' => $exam->id, 'description' => "Prüfung '{$exam->title}' wurde erstellt."]);

        PotentiallyNotifiableActionOccurred::dispatch(
            'Admin\ExamController@store', Auth::user(), $exam, Auth::user()
        );

        return redirect()->route('admin.exams.index');
    }

    public function show(Exam $exam)
    {
        $exam->load('questions.options'); // Ohne trainingModule
        return view('admin.exams.show', compact('exam'));
    }

    public function edit(Exam $exam)
    {
        $exam->load('questions.options');
        // Keine Module mehr nötig

        // Logik zur Aufbereitung der JSON-Daten für Vue/JS
        $initialData = old('questions');
        if (!$initialData && $exam->relationLoaded('questions')) { // Prüfen ob Relation geladen ist
            $initialData = $exam->questions->map(function ($q) {
                $data = [
                    'id' => $q->id,
                    'question_text' => $q->question_text,
                    'type' => $q->type,
                    'options' => $q->relationLoaded('options') ? $q->options->map(function($o) { // Prüfen ob Relation geladen
                        return [
                            'id' => $o->id, 'option_text' => $o->option_text, 'is_correct' => (bool)$o->is_correct
                        ];
                    })->all() : [] // Fallback auf leeres Array
                ];
                if ($q->type === 'single_choice' && $q->relationLoaded('options')) { // Prüfen ob Relation geladen
                    $correctIndex = $q->options->search(fn($o) => $o->is_correct);
                    $data['correct_option'] = $correctIndex !== false ? $correctIndex : null;
                }
                return $data;
            })->all();
        } elseif (!$initialData) {
             $initialData = []; // Fallback, falls Fragen nicht geladen wurden
        }
        $questionsJson = json_encode($initialData ?? []); // Sicherstellen, dass $initialData existiert

        return view('admin.exams.edit', compact('exam', 'questionsJson')); // Ohne 'modules'
    }


    public function update(UpdateExamRequest $request, Exam $exam)
    {
        $exam = $this->examService->updateExam($exam, $request->validated());

        ActivityLog::create(['user_id' => Auth::id(), 'log_type' => 'EXAM', 'action' => 'UPDATED', 'target_id' => $exam->id, 'description' => "Prüfung '{$exam->title}' wurde aktualisiert."]);

        PotentiallyNotifiableActionOccurred::dispatch(
            'Admin\ExamController@update', Auth::user(), $exam, Auth::user()
        );

        return redirect()->route('admin.exams.index');
    }

    public function destroy(Exam $exam)
    {
        $examTitle = $exam->title;
        $examId = $exam->id;
        $deletedExamData = $exam->toArray(); // Event-Daten sichern
        $exam->delete();

        ActivityLog::create(['user_id' => Auth::id(), 'log_type' => 'EXAM', 'action' => 'DELETED', 'target_id' => $examId, 'description' => "Prüfung '{$examTitle}' wurde gelöscht."]);

        PotentiallyNotifiableActionOccurred::dispatch(
            'Admin\ExamController@destroy',
            Auth::user(),
            (object) $deletedExamData, // Als Objekt übergeben
            Auth::user(),
            ['title' => $examTitle] // Zusätzliche Daten für den Listener
        );


        return redirect()->route('admin.exams.index');
    }
}
