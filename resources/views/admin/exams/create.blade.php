@extends('layouts.app')
@section('title', 'Neue Prüfung erstellen')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-file-signature nav-icon"></i> Neue Prüfung erstellen</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('modules.index') }}">Module</a></li>
                    <li class="breadcrumb-item active">Prüfung erstellen</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="content">
    <div class="container-fluid">
        <form action="{{ route('admin.exams.store') }}" method="POST">
            @csrf
            <div class="row">
                {{-- Linke Spalte: Prüfungs-Stammdaten --}}
                <div class="col-lg-4">
                    <div class="card card-primary card-outline sticky-top">
                        <div class="card-header"><h3 class="card-title">Prüfungsdetails</h3></div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="training_module_id">Zugehöriges Modul</label>
                                <select name="training_module_id" class="form-control @error('training_module_id') is-invalid @enderror" required>
                                    <option value="">Bitte auswählen...</option>
                                    @foreach($modules as $module)
                                    <option value="{{ $module->id }}" {{ old('training_module_id') == $module->id ? 'selected' : '' }}>{{ $module->name }}</option>
                                    @endforeach
                                </select>
                                @error('training_module_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group">
                                <label for="title">Titel der Prüfung</label>
                                <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" required>
                                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group">
                                <label for="pass_mark">Bestehensgrenze (in %)</label>
                                <input type="number" name="pass_mark" class="form-control @error('pass_mark') is-invalid @enderror" value="{{ old('pass_mark', 75) }}" min="1" max="100" required>
                                @error('pass_mark')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group">
                                <label for="description">Beschreibung / Anweisungen</label>
                                <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Rechte Spalte: Fragen & Antworten --}}
                <div class="col-lg-8">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">Fragenkatalog</h3>
                            <div class="card-tools">
                                <button type="button" id="add-question-btn" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus"></i> Frage hinzufügen
                                </button>
                            </div>
                        </div>
                        <div class="card-body" id="questions-container">
                            {{-- Fragen werden hier dynamisch per JS eingefügt --}}
                            @if(!old('questions'))
                                <p class="text-muted text-center">Fügen Sie die erste Frage hinzu.</p>
                            @endif
                        </div>
                        <div class="card-footer">
                            <a href="{{ route('modules.index') }}" class="btn btn-secondary">Abbrechen</a>
                            <button type="submit" class="btn btn-success float-right"><i class="fas fa-save"></i> Prüfung speichern</button>
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
document.addEventListener('DOMContentLoaded', function() {
    let questionIndex = 0;
    const container = document.getElementById('questions-container');
    const addQuestionBtn = document.getElementById('add-question-btn');

    // Funktion zum Hinzufügen einer Frage
    function addQuestion() {
        if (questionIndex === 0) {
            container.innerHTML = ''; // Leere die "Keine Fragen"-Nachricht
        }

        const questionId = `q${questionIndex}`;
        const questionHtml = `
            <div class="question-block card card-outline card-secondary mb-3" id="${questionId}">
                <div class="card-header">
                    <h3 class="card-title">Frage ${questionIndex + 1}</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-sm btn-danger remove-question-btn" data-target="${questionId}"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="${questionId}_text">Fragentext</label>
                        <textarea name="questions[${questionIndex}][question_text]" id="${questionId}_text" class="form-control" rows="2" required></textarea>
                    </div>
                    <label>Antwortmöglichkeiten (Markieren Sie die korrekte Antwort)</label>
                    <div class="options-container">
                        {{-- Optionen werden hier eingefügt --}}
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-2 add-option-btn" data-qindex="${questionIndex}">Antwort hinzufügen</button>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', questionHtml);
        
        // Füge standardmäßig 2 Antwortmöglichkeiten hinzu
        addOption(questionIndex, document.querySelector(`#${questionId} .options-container`));
        addOption(questionIndex, document.querySelector(`#${questionId} .options-container`));

        questionIndex++;
    }

    // Funktion zum Hinzufügen einer Antwortmöglichkeit
    function addOption(qIndex, optionsContainer) {
        const optionIndex = optionsContainer.children.length;
        const optionHtml = `
            <div class="input-group mt-2">
                <div class="input-group-prepend">
                    <div class="input-group-text">
                        <input type="radio" name="questions[${qIndex}][correct_option]" value="${optionIndex}" required ${optionIndex === 0 ? 'checked' : ''}>
                    </div>
                </div>
                <input type="text" name="questions[${qIndex}][options][${optionIndex}][option_text]" class="form-control" required>
                <div class="input-group-append">
                    <button type="button" class="btn btn-outline-danger remove-option-btn"><i class="fas fa-times"></i></button>
                </div>
            </div>
        `;
        optionsContainer.insertAdjacentHTML('beforeend', optionHtml);
    }

    // Event Listeners
    addQuestionBtn.addEventListener('click', addQuestion);

    container.addEventListener('click', function(e) {
        // Frage entfernen
        if (e.target.closest('.remove-question-btn')) {
            const targetId = e.target.closest('.remove-question-btn').dataset.target;
            document.getElementById(targetId).remove();
        }
        // Antwortmöglichkeit hinzufügen
        if (e.target.closest('.add-option-btn')) {
            const btn = e.target.closest('.add-option-btn');
            const qIndex = btn.dataset.qindex;
            const optionsContainer = btn.previousElementSibling;
            addOption(qIndex, optionsContainer);
        }
        // Antwortmöglichkeit entfernen
        if (e.target.closest('.remove-option-btn')) {
            e.target.closest('.input-group').remove();
        }
    });

    // Wiederherstellen des Formulars bei Validierungsfehlern
    @if(old('questions'))
        const oldQuestions = @json(old('questions'));
        oldQuestions.forEach((question, qIdx) => {
            addQuestion();
            const currentBlock = document.getElementById(`q${qIdx}`);
            currentBlock.querySelector('textarea').value = question.question_text;
            
            const optionsContainer = currentBlock.querySelector('.options-container');
            optionsContainer.innerHTML = ''; // Leere die Standard-Optionen

            question.options.forEach((option, oIdx) => {
                addOption(qIdx, optionsContainer);
                const currentOptionGroup = optionsContainer.children[oIdx];
                currentOptionGroup.querySelector('input[type=text]').value = option.option_text;
                if (question.correct_option == oIdx) {
                    currentOptionGroup.querySelector('input[type=radio]').checked = true;
                }
            });
        });
    @endif
});
</script>
@endpush
