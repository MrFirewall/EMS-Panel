<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\TrainingModule;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use App\Events\PotentiallyNotifiableActionOccurred;
use App\Services\ExamService; // NEU
use App\Http\Requests\Admin\StoreExamRequest; // NEU
use App\Http\Requests\Admin\UpdateExamRequest; // NEU

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
        $exams = Exam::with('trainingModule')->withCount('questions')->latest()->paginate(15);
        return view('admin.exams.index', compact('exams'));
    }

    public function create()
    {
        $modules = TrainingModule::doesntHave('exam')->orderBy('name')->get();
        return view('admin.exams.create', compact('modules'));
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
        
        // Die Logik zur Aufbereitung der JSON-Daten für Vue/JS bleibt
        $initialData = old('questions');
        if (!$initialData) {
            $initialData = $exam->questions->map(function ($q) {
                $data = [
                    'id' => $q->id,
                    'question_text' => $q->question_text,
                    'type' => $q->type,
                    'options' => $q->options->map(function($o) {
                        return [
                            'id' => $o->id, 'option_text' => $o->option_text, 'is_correct' => (bool)$o->is_correct
                        ];
                    })->all()
                ];
                if ($q->type === 'single_choice') {
                    $correctIndex = $q->options->search(fn($o) => $o->is_correct);
                    $data['correct_option'] = $correctIndex !== false ? $correctIndex : null;
                }
                return $data;
            })->all();
        }
        $questionsJson = json_encode($initialData ?? []);

        return view('admin.exams.edit', compact('exam', 'modules', 'questionsJson'));
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
        $deletedExamData = $exam->toArray(); 
        $exam->delete();

        ActivityLog::create(['user_id' => Auth::id(), 'log_type' => 'EXAM', 'action' => 'DELETED', 'target_id' => $examId, 'description' => "Prüfung '{$examTitle}' wurde gelöscht."]);

        PotentiallyNotifiableActionOccurred::dispatch(
            'Admin\ExamController@destroy', Auth::user(), (object) $deletedExamData, Auth::user()
        );

        return redirect()->route('admin.exams.index');
    }
}
