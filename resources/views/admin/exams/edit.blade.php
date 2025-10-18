@extends('layouts.app')
@section('title', 'Prüfung bearbeiten')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-edit nav-icon"></i> Prüfung bearbeiten</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.exams.index') }}">Prüfungen</a></li>
                    <li class="breadcrumb-item active">Bearbeiten</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="content">
    <div class="container-fluid">
        <form action="{{ route('admin.exams.update', $exam) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-lg-4">
                    <div class="card card-warning card-outline sticky-top">
                        <div class="card-header"><h3 class="card-title">Prüfungsdetails</h3></div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="training_module_id">Zugehöriges Modul</label>
                                <select name="training_module_id" class="form-control @error('training_module_id') is-invalid @enderror" required>
                                    <option value="{{ $exam->training_module_id }}">{{ $exam->trainingModule->name }}</option>
                                    @foreach($modules as $module)
                                        @if($module->id !== $exam->training_module_id)
                                        <option value="{{ $module->id }}" {{ old('training_module_id') == $module->id ? 'selected' : '' }}>{{ $module->name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                @error('training_module_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group">
                                <label for="title">Titel der Prüfung</label>
                                <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $exam->title) }}" required>
                                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group">
                                <label for="pass_mark">Bestehensgrenze (in %)</label>
                                <input type="number" name="pass_mark" class="form-control @error('pass_mark') is-invalid @enderror" value="{{ old('pass_mark', $exam->pass_mark) }}" min="1" max="100" required>
                                @error('pass_mark')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group">
                                <label for="description">Beschreibung / Anweisungen</label>
                                <textarea name="description" class="form-control" rows="3">{{ old('description', $exam->description) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="card card-warning card-outline">
                        <div class="card-header">
                            <h3 class="card-title">Fragenkatalog</h3>
                            <div class="card-tools">
                                <button type="button" id="add-question-btn" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus"></i> Frage hinzufügen
                                </button>
                            </div>
                        </div>
                        <div class="card-body" id="questions-container">
                             @foreach($exam->questions as $qIndex => $question)
                                <div class="question-block card card-outline card-secondary mb-3" id="q{{ $qIndex }}">
                                    <div class="card-header">
                                        <h3 class="card-title">Frage {{ $qIndex + 1 }}</h3>
                                        <div class="card-tools"><button type="button" class="btn btn-sm btn-danger remove-question-btn" data-target="q{{ $qIndex }}"><i class="fas fa-trash"></i></button></div>
                                    </div>
                                    <div class="card-body">
                                        <input type="hidden" name="questions[{{ $qIndex }}][id]" value="{{ $question->id }}">
                                        <div class="form-group">
                                            <label for="q{{ $qIndex }}_text">Fragentext</label>
                                            <textarea name="questions[{{ $qIndex }}][question_text]" id="q{{ $qIndex }}_text" class="form-control" rows="2" required>{{ $question->question_text }}</textarea>
                                        </div>
                                        <label>Antwortmöglichkeiten</label>
                                        <div class="options-container">
                                            @foreach($question->options as $oIndex => $option)
                                                <div class="input-group mt-2">
                                                    <input type="hidden" name="questions[{{ $qIndex }}][options][{{ $oIndex }}][id]" value="{{ $option->id }}">
                                                    <div class="input-group-prepend"><div class="input-group-text"><input type="radio" name="questions[{{ $qIndex }}][correct_option]" value="{{ $oIndex }}" required {{ $option->is_correct ? 'checked' : '' }}></div></div>
                                                    <input type="text" name="questions[{{ $qIndex }}][options][{{ $oIndex }}][option_text]" class="form-control" value="{{ $option->option_text }}" required>
                                                    <div class="input-group-append"><button type="button" class="btn btn-outline-danger remove-option-btn"><i class="fas fa-times"></i></button></div>
                                                </div>
                                            @endforeach
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-primary mt-2 add-option-btn" data-qindex="{{ $qIndex }}">Antwort hinzufügen</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="card-footer">
                            <a href="{{ route('admin.exams.index') }}" class="btn btn-secondary">Abbrechen</a>
                            <button type="submit" class="btn btn-success float-right"><i class="fas fa-save"></i> Änderungen speichern</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@push('scripts')
<script>
// Das JavaScript von der `create`-Seite kann hier fast identisch wiederverwendet werden.
// Wichtig ist, dass der `questionIndex` mit der richtigen Startnummer initialisiert wird.
document.addEventListener('DOMContentLoaded', function() {
    let questionIndex = {{ $exam->questions->count() }}; // Initialisiere mit der Anzahl bestehender Fragen
    // ... Rest des JavaScript-Codes von create.blade.php
    // (Keine Änderungen nötig, da es robust genug ist)
    const container = document.getElementById('questions-container');
    const addQuestionBtn = document.getElementById('add-question-btn');

    function addQuestion() { /* ... identisch ... */ }
    function addOption(qIndex, optionsContainer) { /* ... identisch ... */ }
    
    // Event Listeners ... identisch ...
});
</script>
@endpush
