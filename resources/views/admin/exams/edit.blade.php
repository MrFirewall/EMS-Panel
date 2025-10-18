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
                                        <div class="card-tools"><button type="button" class="btn btn-sm btn-danger remove-question-btn"><i class="fas fa-trash"></i></button></div>
                                    </div>
                                    <div class="card-body">
                                        <input type="hidden" name="questions[{{ $qIndex }}][id]" value="{{ $question->id }}">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <div class="form-group">
                                                    <label>Fragentext</label>
                                                    <textarea name="questions[{{ $qIndex }}][question_text]" class="form-control" rows="2" required>{{ $question->question_text }}</textarea>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Fragetyp</label>
                                                    <select name="questions[{{ $qIndex }}][type]" class="form-control question-type-select">
                                                        <option value="single_choice" {{ $question->type == 'single_choice' ? 'selected' : '' }}>Einzelantwort</option>
                                                        <option value="multiple_choice" {{ $question->type == 'multiple_choice' ? 'selected' : '' }}>Mehrfachantwort</option>
                                                        <option value="text_field" {{ $question->type == 'text_field' ? 'selected' : '' }}>Textfeld</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="options-wrapper" style="{{ $question->type == 'text_field' ? 'display: none;' : '' }}">
                                            <label>Antwortmöglichkeiten</label>
                                            <div class="options-container">
                                                @foreach($question->options as $oIndex => $option)
                                                    <div class="input-group mt-2">
                                                        <input type="hidden" name="questions[{{ $qIndex }}][options][{{ $oIndex }}][id]" value="{{ $option->id }}">
                                                        <div class="input-group-prepend"><div class="input-group-text">
                                                            @if($question->type == 'single_choice')
                                                                <input type="radio" name="questions[{{ $qIndex }}][correct_option]" value="{{ $oIndex }}" required {{ $option->is_correct ? 'checked' : '' }}>
                                                            @else
                                                                <input type="checkbox" name="questions[{{ $qIndex }}][options][{{ $oIndex }}][is_correct]" value="1" {{ $option->is_correct ? 'checked' : '' }}>
                                                            @endif
                                                        </div></div>
                                                        <input type="text" name="questions[{{ $qIndex }}][options][{{ $oIndex }}][option_text]" class="form-control" value="{{ $option->option_text }}" required>
                                                        <div class="input-group-append"><button type="button" class="btn btn-outline-danger remove-option-btn"><i class="fas fa-times"></i></button></div>
                                                    </div>
                                                @endforeach
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-primary mt-2 add-option-btn" data-qindex="{{ $qIndex }}">Antwort hinzufügen</button>
                                        </div>
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
@endsection
@push('scripts')
<script>
// Das Skript ist identisch mit dem aus der `create`-Ansicht,
// aber `questionIndex` wird mit der Anzahl der bestehenden Fragen initialisiert.
document.addEventListener('DOMContentLoaded', function() {
    let questionIndex = {{ $exam->questions->count() }};
    // ... (der gesamte JavaScript-Code von der `create`-Seite wird hier eingefügt)
});
</script>
@endpush

