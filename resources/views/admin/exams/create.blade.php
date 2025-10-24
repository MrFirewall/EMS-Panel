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
                            {{-- Modul-Dropdown entfernt --}}
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
                                <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description') }}</textarea>
                                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                             {{-- Fehlermeldungen für Fragen anzeigen --}}
                             @error('questions') <div class="alert alert-danger">{{ $message }}</div> @enderror
                             @error('questions.*') <div class="alert alert-danger">{{ $message }}</div> @enderror
                            @if(!old('questions'))
                                <p class="text-muted text-center">Fügen Sie die erste Frage hinzu.</p>
                            @endif
                            {{-- Dynamischer Inhalt wird hier durch JS eingefügt --}}
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
{{-- JavaScript für dynamische Fragen bleibt unverändert --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    let questionIndex = 0;
    const container = document.getElementById('questions-container');
    const addQuestionBtn = document.getElementById('add-question-btn');

    function addQuestion() {
        if (questionIndex === 0 && !container.querySelector('.question-block')) {
            container.innerHTML = ''; // Platzhalter entfernen
        }

        const qIndex = questionIndex; // Aktuellen Index sichern
        const questionId = `q${qIndex}`;
        const questionHtml = `
            <div class="question-block card card-outline card-secondary mb-3" id="${questionId}">
                <div class="card-header">
                    <h3 class="card-title">Frage ${qIndex + 1}</h3>
                    <div class="card-tools"><button type="button" class="btn btn-sm btn-danger remove-question-btn"><i class="fas fa-trash"></i></button></div>
                </div>
                <div class="card-body">
                    <input type="hidden" name="questions[${qIndex}][id]" value="">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="${questionId}_text">Fragentext</label>
                                <textarea name="questions[${qIndex}][question_text]" id="${questionId}_text" class="form-control" rows="2" required></textarea>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="${questionId}_type">Fragetyp</label>
                                <select name="questions[${qIndex}][type]" class="form-control question-type-select">
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
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2 add-option-btn" data-qindex="${qIndex}">Antwort hinzufügen</button>
                    </div>
                </div>
            </div>`;
        container.insertAdjacentHTML('beforeend', questionHtml);

        const newQuestionBlock = document.getElementById(questionId);
        const optionsContainer = newQuestionBlock.querySelector('.options-container');
        // Standardmäßig 2 Optionen für Single Choice hinzufügen
        addOption(qIndex, optionsContainer, 'single_choice');
        addOption(qIndex, optionsContainer, 'single_choice');

        questionIndex++; // Index für die nächste Frage erhöhen
    }

    function addOption(qIndex, optionsContainer, type) {
        const optionIndex = optionsContainer.children.length;
        const inputType = type === 'single_choice' ? 'radio' : 'checkbox';
        const inputName = type === 'single_choice'
            ? `questions[${qIndex}][correct_option]`
            : `questions[${qIndex}][options][${optionIndex}][is_correct]`;
        const inputValue = type === 'single_choice' ? optionIndex : '1';
        const inputRequired = type === 'single_choice' ? 'required' : ''; // Radio muss ausgewählt sein
        const textRequired = true; // Text muss immer ausgefüllt sein

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

        // Bei Single Choice die erste Option standardmäßig auswählen
        if (type === 'single_choice' && optionIndex === 0) {
            optionsContainer.querySelector('input[type="radio"]').checked = true;
        }
    }

    addQuestionBtn.addEventListener('click', addQuestion);

    container.addEventListener('click', function(e) {
        const removeQuestionBtn = e.target.closest('.remove-question-btn');
        const addOptionBtn = e.target.closest('.add-option-btn');
        const removeOptionBtn = e.target.closest('.remove-option-btn');

        if (removeQuestionBtn) {
             removeQuestionBtn.closest('.question-block').remove();
             // Wenn keine Fragen mehr da sind, Platzhalter wieder anzeigen
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
            // Nur löschen, wenn mehr als 2 Optionen vorhanden sind
            if (optionsContainer.children.length > 2) {
                removeOptionBtn.closest('.input-group').remove();
            } else {
                alert('Eine Auswahlfrage muss mindestens zwei Antwortmöglichkeiten haben.');
            }
        }
    });

    container.addEventListener('change', function(e) {
        if(e.target.classList.contains('question-type-select')) {
            const questionBlock = e.target.closest('.question-block');
            const optionsWrapper = questionBlock.querySelector('.options-wrapper');
            const optionsContainer = optionsWrapper.querySelector('.options-container');
            const newType = e.target.value;

            // Alle relevanten Inputs in den Optionen finden
            const optionTextInputs = optionsContainer.querySelectorAll('input[type="text"]');
            const optionChoiceInputs = optionsContainer.querySelectorAll('input[type="radio"], input[type="checkbox"]');

            if (newType === 'text_field') {
                optionsWrapper.style.display = 'none';
                // 'required' von allen versteckten Feldern entfernen
                optionTextInputs.forEach(input => input.required = false);
                optionChoiceInputs.forEach(input => input.required = false);
            } else {
                optionsWrapper.style.display = 'block';
                // 'required' für die Textfelder wiederherstellen
                optionTextInputs.forEach(input => input.required = true);

                // Logik zum Umwandeln der Input-Typen (Radio/Checkbox)
                const options = optionsContainer.querySelectorAll('.input-group');
                let hasCheckedRadio = false; // Für single_choice

                options.forEach((optionGroup, oIndex) => {
                    const currentChoiceInput = optionGroup.querySelector('input[type="radio"], input[type="checkbox"]');
                    const nameParts = currentChoiceInput.name.split('[');
                    // Sicherstellen, dass der Index korrekt extrahiert wird
                    const qIndexMatch = currentChoiceInput.name.match(/questions\[(\d+)\]/);
                    if (!qIndexMatch) return; // Überspringen, wenn Index nicht gefunden
                    const qIndex = qIndexMatch[1];

                    let newElement = document.createElement('input');

                    if(newType === 'single_choice') {
                        newElement.type = 'radio';
                        newElement.name = `questions[${qIndex}][correct_option]`;
                        newElement.value = oIndex; // Wert ist der Index
                        newElement.required = true;
                        // Erste Option auswählen oder die, die vorher ausgewählt war (falls von Checkbox gewechselt)
                        if (!hasCheckedRadio) {
                            newElement.checked = true;
                            hasCheckedRadio = true;
                        }
                    } else { // multiple_choice
                        newElement.type = 'checkbox';
                        newElement.name = `questions[${qIndex}][options][${oIndex}][is_correct]`;
                        newElement.value = '1';
                        newElement.required = false;
                        // Behalte den Status bei, wenn von Radio gewechselt wurde
                        if (currentChoiceInput.checked) {
                             newElement.checked = true;
                         }
                    }
                     // Ersetze das alte Input-Element
                    currentChoiceInput.parentNode.replaceChild(newElement, currentChoiceInput);
                });
                 // Sicherstellen, dass bei Wechsel zu single_choice nur EINE Option checked ist
                 if (newType === 'single_choice' && optionsContainer.querySelectorAll('input[type="radio"]:checked').length > 1) {
                     const radios = optionsContainer.querySelectorAll('input[type="radio"]');
                     radios.forEach((radio, index) => radio.checked = (index === 0));
                 }
                 // Sicherstellen, dass bei Wechsel zu single_choice mindestens 2 Optionen vorhanden sind
                 if(options.length < 2 && newType !== 'text_field') {
                     const qIndexMatch = e.target.name.match(/questions\[(\d+)\]/);
                     if(qIndexMatch) {
                         addOption(qIndexMatch[1], optionsContainer, newType);
                         if (options.length + 1 < 2) { // Falls immer noch weniger als 2
                            addOption(qIndexMatch[1], optionsContainer, newType);
                         }
                     }
                 }
            }
        }
    });

    // Wiederherstellen des Formulars bei Validierungsfehlern
    @if(old('questions'))
        let restoredQuestionIndex = 0;
        const oldQuestions = @json(old('questions'));
        oldQuestions.forEach((questionData) => {
             if (container.children.length === 1 && container.children[0].tagName === 'P') {
                container.innerHTML = ''; // Platzhalter entfernen, falls vorhanden
             }
             addQuestion(); // Fügt eine leere Frage hinzu, erhöht den Index

             const currentBlock = document.getElementById(`q${restoredQuestionIndex}`);
             if (!currentBlock) return; // Sicherheitsprüfung

             // Fülle die Felder der gerade hinzugefügten leeren Frage
             currentBlock.querySelector('textarea[name*="question_text"]').value = questionData.question_text || '';
             const typeSelect = currentBlock.querySelector('select[name*="type"]');
             typeSelect.value = questionData.type || 'single_choice';

             // Event manuell auslösen, um Anzeige und 'required' zu steuern
             typeSelect.dispatchEvent(new Event('change', { bubbles: true }));

             const optionsWrapper = currentBlock.querySelector('.options-wrapper');
             const optionsContainer = optionsWrapper.querySelector('.options-container');
             optionsContainer.innerHTML = ''; // Standard-Optionen von addQuestion() entfernen

             // Fülle die Optionen nur, wenn es kein Textfeld ist
             if (questionData.type !== 'text_field' && questionData.options && Array.isArray(questionData.options)) {
                questionData.options.forEach((optionData, oIdx) => {
                    // Füge die Option hinzu (wird mit korrekten Typen erstellt)
                    addOption(restoredQuestionIndex, optionsContainer, questionData.type);
                    const currentOptionGroup = optionsContainer.children[oIdx];
                    if (!currentOptionGroup) return; // Sicherheitsprüfung

                    currentOptionGroup.querySelector('input[type=text]').value = optionData.option_text || '';
                    const choiceInput = currentOptionGroup.querySelector('input[type=radio], input[type=checkbox]');

                    // Setze den checked Status korrekt
                    if (questionData.type === 'single_choice' && questionData.correct_option == oIdx) {
                        if (choiceInput.type === 'radio') choiceInput.checked = true;
                    } else if (questionData.type === 'multiple_choice' && (optionData.is_correct ?? '0') == '1') {
                         if (choiceInput.type === 'checkbox') choiceInput.checked = true;
                    }
                });
             }
             restoredQuestionIndex++; // Index für die nächste alte Frage erhöhen
        });
        questionIndex = restoredQuestionIndex; // Setze den globalen Zähler korrekt
    @endif
});
</script>
@endpush
