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
                {{-- Linke Spalte: Prüfungs-Stammdaten --}}
                <div class="col-lg-4">
                    <div class="card card-warning card-outline sticky-top">
                        <div class="card-header"><h3 class="card-title">Prüfungsdetails</h3></div>
                        <div class="card-body">
                           <div class="form-group">
                                <label for="training_module_id">Zugehöriges Modul</label>
                                <select name="training_module_id" class="form-control @error('training_module_id') is-invalid @enderror" required>
                                    {{-- Zeige das aktuell zugewiesene Modul immer an (auch wenn es voll ist) --}}
                                    <option value="{{ $exam->training_module_id }}">{{ $exam->trainingModule->name }}</option>
                                    
                                    @foreach($modules as $module)
                                        {{-- Zeige die anderen verfügbaren Module an --}}
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

                {{-- Rechte Spalte: Fragen & Antworten --}}
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
                            {{-- Container für dynamische Fragen --}}
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
document.addEventListener('DOMContentLoaded', function() {
    let questionIndex = 0;
    const container = document.getElementById('questions-container');
    const addQuestionBtn = document.getElementById('add-question-btn');

    // KORRIGIERT: Daten werden jetzt sauber als JSON vom Controller übergeben
    // {!! $questionsJson !!} rendert den JSON-String direkt ins JavaScript
    const initialData = {!! $questionsJson !!};

    function addQuestion(questionData = null) {
        const qIndex = questionIndex;
        const questionId = `q${qIndex}`;
        const isNew = questionData === null;
        
        // Daten sicher abrufen, auch wenn sie neu sind
        const qId = questionData?.id || '';
        const qText = questionData?.question_text || '';
        const qType = questionData?.type || 'single_choice';

        const questionHtml = `
            <div class="question-block card card-outline card-secondary mb-3" id="${questionId}">
                <div class="card-header">
                    <h3 class="card-title">${isNew ? 'Neue Frage' : `Frage ${qIndex + 1}`}</h3>
                    <div class="card-tools"><button type="button" class="btn btn-sm btn-danger remove-question-btn"><i class="fas fa-trash"></i></button></div>
                </div>
                <div class="card-body">
                    <input type="hidden" name="questions[${qIndex}][id]" value="${qId}">
                    <div class="row">
                        <div class="col-md-8"><div class="form-group"><label>Fragentext</label><textarea name="questions[${qIndex}][question_text]" class="form-control" rows="2" required>${qText}</textarea></div></div>
                        <div class="col-md-4"><div class="form-group"><label>Fragetyp</label><select name="questions[${qIndex}][type]" class="form-control question-type-select">
                            <option value="single_choice" ${qType == 'single_choice' ? 'selected' : ''}>Einzelantwort</option>
                            <option value="multiple_choice" ${qType == 'multiple_choice' ? 'selected' : ''}>Mehrfachantwort</option>
                            <option value="text_field" ${qType == 'text_field' ? 'selected' : ''}>Textfeld</option>
                        </select></div></div>
                    </div>
                    <div class="options-wrapper" style="${qType == 'text_field' ? 'display: none;' : ''}">
                        <label>Antwortmöglichkeiten</label>
                        <div class="options-container"></div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2 add-option-btn" data-qindex="${qIndex}">Antwort hinzufügen</button>
                    </div>
                </div>
            </div>`;
        container.insertAdjacentHTML('beforeend', questionHtml);

        const newQuestionBlock = document.getElementById(questionId);
        const optionsContainer = newQuestionBlock.querySelector('.options-container');
        
        // Fülle die Optionen basierend auf den `questionData`
        if (questionData?.options && questionData.options.length > 0) {
            
            // `correct_option` ist der *Index* (0, 1, 2...) aus old() oder DB-Transformation
            const correctRadioIndex = questionData.correct_option !== undefined ? parseInt(questionData.correct_option) : null;

            questionData.options.forEach((optionData, oIndex) => {
                // Setze 'is_correct' für single_choice-Fragen basierend auf dem Index
                if (qType === 'single_choice') {
                    // Wenn 'correct_option' (der Index) existiert, nutze ihn.
                    if (correctRadioIndex !== null) {
                        optionData.is_correct = (oIndex === correctRadioIndex);
                    }
                    // 'is_correct' aus der DB-Transformation wird ebenfalls berücksichtigt
                }
                addOption(qIndex, optionsContainer, qType, optionData);
            });
        } else if (isNew && qType !== 'text_field') {
            // Füge 2 leere Optionen für eine neue Auswahl-Frage hinzu
            addOption(qIndex, optionsContainer, 'single_choice');
            addOption(qIndex, optionsContainer, 'single_choice');
        }
        
        questionIndex++;
    }

    function addOption(qIndex, optionsContainer, type, optionData = null) {
        const optionIndex = optionsContainer.children.length;
        const inputType = type === 'single_choice' ? 'radio' : 'checkbox';
        
        const optId = optionData?.id || '';
        const optText = optionData?.option_text || '';
        
        let inputName, inputValue, checked, required;
        
        if(type === 'single_choice') {
            inputName = `questions[${qIndex}][correct_option]`;
            inputValue = optionIndex; // Der Wert ist der Index der Option
            // `optionData.is_correct` ist jetzt ein boolean (dank Controller)
            checked = optionData ? (optionData.is_correct) : (optionIndex === 0); // Standard: Erste Option ist ausgewählt
            required = 'required';
        } else { // multiple_choice
            inputName = `questions[${qIndex}][options][${optionIndex}][is_correct]`;
            inputValue = '1'; // Der Wert ist immer 1, wenn angehakt
            checked = optionData ? (optionData.is_correct) : false;
            required = '';
        }

        const optionHtml = `
            <div class="input-group mt-2">
                <input type="hidden" name="questions[${qIndex}][options][${optionIndex}][id]" value="${optId}">
                <div class="input-group-prepend"><div class="input-group-text"><input type="${inputType}" name="${inputName}" value="${inputValue}" ${checked ? 'checked' : ''} ${required}></div></div>
                <input type="text" name="questions[${qIndex}][options][${optionIndex}][option_text]" class="form-control" value="${optText}" required>
                <div class="input-group-append"><button type="button" class="btn btn-outline-danger remove-option-btn"><i class="fas fa-times"></i></button></div>
            </div>`;
        optionsContainer.insertAdjacentHTML('beforeend', optionHtml);
    }
    
    // Initiales Rendern des Formulars
    initialData.forEach(qData => addQuestion(qData));
    if (initialData.length === 0) {
        container.innerHTML = '<p class="text-muted text-center">Fügen Sie die erste Frage hinzu.</p>';
    }

    addQuestionBtn.addEventListener('click', () => {
        // Wenn vorher keine Fragen da waren, lösche den Platzhaltertext
        if (questionIndex === 0) {
            container.innerHTML = '';
        }
        addQuestion();
    });

    container.addEventListener('click', function(e) {
        const removeQuestionBtn = e.target.closest('.remove-question-btn');
        const addOptionBtn = e.target.closest('.add-option-btn');
        const removeOptionBtn = e.target.closest('.remove-option-btn');

        if (removeQuestionBtn) {
            removeQuestionBtn.closest('.question-block').remove();
            // Optional: Wenn alle Fragen gelöscht wurden, Platzhalter anzeigen
            if (container.children.length === 0) {
                questionIndex = 0; // Zähler zurücksetzen
                container.innerHTML = '<p class="text-muted text-center">Fügen Sie die erste Frage hinzu.</p>';
            }
        }
        
        if (addOptionBtn) {
            const qIndex = addOptionBtn.dataset.qindex;
            const questionBlock = addOptionBtn.closest('.question-block');
            const type = questionBlock.querySelector('.question-type-select').value;
            const optionsContainer = addOptionBtn.closest('.options-wrapper').querySelector('.options-container');
            addOption(qIndex, optionsContainer, type);
        }
        if (removeOptionBtn) {
            const optionsContainer = removeOptionBtn.closest('.options-container');
            // Erlaube das Löschen, bis nur noch 2 Optionen übrig sind
            if (optionsContainer.children.length > 2) { 
                removeOptionBtn.closest('.input-group').remove();
            } else {
                alert('Eine Auswahlfrage muss mindestens zwei Antwortmöglichkeiten haben.');
            }
        }
    });

    container.addEventListener('change', function(e) {
        // Wenn der Fragetyp geändert wird
        if(e.target.classList.contains('question-type-select')) {
            const questionBlock = e.target.closest('.question-block');
            const optionsWrapper = questionBlock.querySelector('.options-wrapper');
            const optionsContainer = optionsWrapper.querySelector('.options-container');
            const newType = e.target.value;

            if (newType === 'text_field') {
                optionsWrapper.style.display = 'none';
                // Alte Optionen leeren, um Validierungsfehler zu vermeiden
                optionsContainer.innerHTML = ''; 
            } else {
                optionsWrapper.style.display = 'block';
                const options = optionsContainer.querySelectorAll('.input-group');
                let hasCheckedRadio = false; // Stellt sicher, dass nur ein Radio-Button gecheckt wird
                
                // Wenn von Textfeld gewechselt wird und keine Optionen da sind, 2 hinzufügen
                if (options.length === 0) {
                    const qIndex = questionBlock.querySelector('input[type="hidden"]').name.match(/\[(\d+)\]/)[1];
                    addOption(qIndex, optionsContainer, newType);
                    addOption(qIndex, optionsContainer, newType);
                    return; // Funktion hier beenden, da die Optionen jetzt existieren
                }

                options.forEach((optionGroup, oIndex) => {
                    const currentInput = optionGroup.querySelector('input[type="radio"], input[type="checkbox"]');
                    const nameParts = currentInput.name.split('[');
                    // Finde den Index der Frage (z.B. 'questions[0][...]')
                    const qIndex = nameParts[1]?.replace(']', ''); 
                    
                    if (qIndex === undefined) return; // Sollte nicht passieren

                    let newElement = document.createElement('input');
                    if (newType === 'single_choice') {
                        newElement.type = 'radio';
                        newElement.name = `questions[${qIndex}][correct_option]`;
                        newElement.value = oIndex;
                        newElement.required = true;
                        // Wähle das erste Element standardmäßig aus
                        if (!hasCheckedRadio) {
                            newElement.checked = true;
                            hasCheckedRadio = true;
                        }
                    } else { // multiple_choice
                        newElement.type = 'checkbox';
                        newElement.name = `questions[${qIndex}][options][${oIndex}][is_correct]`;
                        newElement.value = '1';
                        // Behalte den "checked"-Status bei, wenn von Radio gewechselt wird
                        if (currentInput.checked) {
                            newElement.checked = true;
                        }
                    }
                    // Ersetze das alte Input-Element durch das neue
                    currentInput.parentNode.replaceChild(newElement, currentInput);
                });
            }
        }
    });
});
</script>
@endpush