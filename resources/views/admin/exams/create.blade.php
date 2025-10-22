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
                    {{-- Korrigierter Breadcrumb-Link, falls 'modules.index' nicht existiert --}}
                    <li class="breadcrumb-item"><a href="{{ route('admin.exams.index') }}">Prüfungen</a></li>
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
                            @if(!old('questions'))
                                <p class="text-muted text-center">Fügen Sie die erste Frage hinzu.</p>
                            @endif
                        </div>
                        <div class="card-footer">
                            <a href="{{ route('admin.exams.index') }}" class="btn btn-secondary">Abbrechen</a>
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

    function addQuestion() {
        if (questionIndex === 0 && !container.querySelector('.question-block')) {
            container.innerHTML = '';
        }

        const questionId = `q${questionIndex}`;
        const questionHtml = `
            <div class="question-block card card-outline card-secondary mb-3" id="${questionId}">
                <div class="card-header">
                    <h3 class="card-title">Neue Frage ${questionIndex + 1}</h3>
                    <div class="card-tools"><button type="button" class="btn btn-sm btn-danger remove-question-btn"><i class="fas fa-trash"></i></button></div>
                </div>
                <div class="card-body">
                    <input type="hidden" name="questions[${questionIndex}][id]" value="">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="${questionId}_text">Fragentext</label>
                                <textarea name="questions[${questionIndex}][question_text]" id="${questionId}_text" class="form-control" rows="2" required></textarea>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="${questionId}_type">Fragetyp</label>
                                <select name="questions[${questionIndex}][type]" class="form-control question-type-select">
                                    <option value="single_choice" selected>Einzelantwort</option>
                                    <option value="multiple_choice">Mehrfachantwort</option>
                                    <option value="text_field">Textfeld</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="options-wrapper">
                        <label>Antwortmöglichkeiten</label>
                        <div class="options-container"></div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2 add-option-btn" data-qindex="${questionIndex}">Antwort hinzufügen</button>
                    </div>
                </div>
            </div>`;
        container.insertAdjacentHTML('beforeend', questionHtml);
        
        const newQuestionBlock = document.getElementById(questionId);
        const optionsContainer = newQuestionBlock.querySelector('.options-container');
        addOption(questionIndex, optionsContainer, 'single_choice');
        addOption(questionIndex, optionsContainer, 'single_choice');

        questionIndex++;
    }

    function addOption(qIndex, optionsContainer, type) {
        const optionIndex = optionsContainer.children.length;
        const inputType = type === 'single_choice' ? 'radio' : 'checkbox';
        const inputName = type === 'single_choice' 
            ? `questions[${qIndex}][correct_option]` 
            : `questions[${qIndex}][options][${optionIndex}][is_correct]`;
        const inputValue = type === 'single_choice' ? optionIndex : '1';
        // 'required' für Radio-Buttons (single_choice)
        const inputRequired = type === 'single_choice' ? 'required' : '';
        // 'required' für das Textfeld (immer)
        const textRequired = true;

        const optionHtml = `
            <div class="input-group mt-2">
                <input type="hidden" name="questions[${qIndex}][options][${optionIndex}][id]" value="">
                <div class="input-group-prepend">
                    <div class="input-group-text"><input type="${inputType}" name="${inputName}" value="${inputValue}" ${inputRequired}></div>
                </div>
                <input type="text" name="questions[${qIndex}][options][${optionIndex}][option_text]" class="form-control" ${textRequired ? 'required' : ''}>
                <div class="input-group-append"><button type="button" class="btn btn-outline-danger remove-option-btn"><i class="fas fa-times"></i></button></div>
            </div>`;
        optionsContainer.insertAdjacentHTML('beforeend', optionHtml);
        
        if (type === 'single_choice' && optionIndex === 0) {
            optionsContainer.querySelector('input[type="radio"]').checked = true;
        }
    }
    
    addQuestionBtn.addEventListener('click', addQuestion);

    container.addEventListener('click', function(e) {
        const removeQuestionBtn = e.target.closest('.remove-question-btn');
        const addOptionBtn = e.target.closest('.add-option-btn');
        const removeOptionBtn = e.target.closest('.remove-option-btn');

        if (removeQuestionBtn) removeQuestionBtn.closest('.question-block').remove();
        
        if (addOptionBtn) {
            const qIndex = addOptionBtn.dataset.qindex;
            const questionBlock = addOptionBtn.closest('.question-block');
            const type = questionBlock.querySelector('.question-type-select').value;
            const optionsContainer = addOptionBtn.closest('.options-wrapper').querySelector('.options-container');
            addOption(qIndex, optionsContainer, type);
        }
        if (removeOptionBtn) {
            const optionsContainer = removeOptionBtn.closest('.options-container');
            if (optionsContainer.children.length > 2) {
                removeOptionBtn.closest('.input-group').remove();
            } else {
                alert('Eine Auswahlfrage muss mindestens zwei Antwortmöglichkeiten haben.');
            }
        }
    });

    /**
     * KORRIGIERTER EVENT LISTENER
     * Fügt Logik hinzu, um 'required' Attribute zu entfernen/hinzuzufügen,
     * wenn der Fragetyp geändert wird.
     */
    container.addEventListener('change', function(e) {
        if(e.target.classList.contains('question-type-select')) {
            const questionBlock = e.target.closest('.question-block');
            const optionsWrapper = questionBlock.querySelector('.options-wrapper');
            const optionsContainer = optionsWrapper.querySelector('.options-container');
            const newType = e.target.value;

            // Alle relevanten Inputs in den Optionen finden
            const optionTextInputs = optionsContainer.querySelectorAll('input[type="text"]');
            const optionRadioInputs = optionsContainer.querySelectorAll('input[type="radio"]');

            if (newType === 'text_field') {
                optionsWrapper.style.display = 'none';
                // 'required' von allen versteckten Feldern entfernen, um Validierungsfehler zu vermeiden
                optionTextInputs.forEach(input => input.required = false);
                optionRadioInputs.forEach(input => input.required = false);

            } else {
                optionsWrapper.style.display = 'block';
                // 'required' für die Textfelder wiederherstellen
                optionTextInputs.forEach(input => input.required = true);

                // Logik zum Umwandeln der Input-Typen (Radio/Checkbox)
                const options = optionsContainer.querySelectorAll('.input-group');
                
                options.forEach((optionGroup, oIndex) => {
                    const radioOrCheckbox = optionGroup.querySelector('input[type="radio"], input[type="checkbox"]');
                    const nameParts = radioOrCheckbox.name.split('[');
                    const qIndex = nameParts[1].replace(']', '');

                    if(newType === 'single_choice') {
                        radioOrCheckbox.type = 'radio';
                        radioOrCheckbox.name = `questions[${qIndex}][correct_option]`;
                        radioOrCheckbox.value = oIndex;
                        radioOrCheckbox.required = true; // 'required' für Radios wiederherstellen
                        radioOrCheckbox.checked = (oIndex === 0);
                    } else { // multiple_choice
                        radioOrCheckbox.type = 'checkbox';
                        radioOrCheckbox.name = `questions[${qIndex}][options][${oIndex}][is_correct]`;
                        radioOrCheckbox.value = '1';
                        radioOrCheckbox.required = false; // Checkboxen sind nie 'required'
                    }
                });
            }
        }
    });

    // Wiederherstellen des Formulars bei Validierungsfehlern
    @if(old('questions'))
        let restoredQuestionIndex = 0;
        const oldQuestions = @json(old('questions'));
        oldQuestions.forEach((questionData, qIdx) => {
            addQuestion();
            const currentBlock = document.getElementById(`q${restoredQuestionIndex}`);
            
            currentBlock.querySelector('textarea[name*="question_text"]').value = questionData.question_text;
            const typeSelect = currentBlock.querySelector('select[name*="type"]');
            typeSelect.value = questionData.type;

            // Event manuell auslösen, um die 'required'-Logik und Anzeige zu steuern
            typeSelect.dispatchEvent(new Event('change', { bubbles: true }));

            const optionsWrapper = currentBlock.querySelector('.options-wrapper');
            const optionsContainer = optionsWrapper.querySelector('.options-container');
            optionsContainer.innerHTML = ''; // Standard-Optionen von addQuestion() entfernen

            if (questionData.type !== 'text_field') {
                // optionsWrapper.style.display = 'block'; // Wird bereits durch 'change' Event gesteuert
                (questionData.options || []).forEach((optionData, oIdx) => {
                    addOption(restoredQuestionIndex, optionsContainer, questionData.type);
                    const currentOptionGroup = optionsContainer.children[oIdx];
                    currentOptionGroup.querySelector('input[type=text]').value = optionData.option_text;
                    
                    if (questionData.type === 'single_choice' && questionData.correct_option == oIdx) {
                        currentOptionGroup.querySelector('input[type=radio]').checked = true;
                    } else if (questionData.type === 'multiple_choice' && (optionData.is_correct ?? false)) {
                        currentOptionGroup.querySelector('input[type=checkbox]').checked = true;
                    }
                });
            } else {
                // optionsWrapper.style.display = 'none'; // Wird bereits durch 'change' Event gesteuert
            }
            restoredQuestionIndex++;
        });
        questionIndex = restoredQuestionIndex;
    @endif
});
</script>
@endpush