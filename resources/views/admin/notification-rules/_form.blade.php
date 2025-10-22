{{-- Dieses Partial wird von create.blade.php und edit.blade.php verwendet --}}
{{-- Die Variable $notificationRule ist in 'edit' gesetzt, in 'create' ist sie null --}}

{{-- Füge Select2 CSS hinzu, falls noch nicht im Hauptlayout geladen --}}
@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@x.x.x/dist/select2-bootstrap4.min.css"> {{-- Theme für Bootstrap 4 --}}
    <style>
        /* Style Anpassungen für Select2 im Dark Mode */
        .dark-mode .select2-container--bootstrap4 .select2-selection {
            background-color: #343a40;
            border-color: #6c757d;
            color: #fff;
        }
        .dark-mode .select2-container--bootstrap4 .select2-dropdown {
            background-color: #343a40;
            border-color: #6c757d;
        }
        .dark-mode .select2-container--bootstrap4 .select2-search--dropdown .select2-search__field {
            background-color: #454d55;
            color: #fff;
        }
         .dark-mode .select2-container--bootstrap4 .select2-results__option {
             color: #dee2e6; /* Hellere Textfarbe für Optionen */
         }
        .dark-mode .select2-container--bootstrap4 .select2-results__option--highlighted {
            background-color: #007bff;
            color: #fff;
        }
         .dark-mode .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
             color: #fff; /* Stellt sicher, dass der ausgewählte Text weiß ist */
         }
    </style>
@endpush

<div class="card-body">
    {{-- Controller Action Dropdown --}}
    <div class="form-group">
        <label for="controller_action">Auslösende Aktion <span class="text-danger">*</span></label>
        <select name="controller_action" id="controller_action" class="form-control select2 @error('controller_action') is-invalid @enderror" required>
            <option value="" disabled {{ old('controller_action', $notificationRule->controller_action ?? '') == '' ? 'selected' : '' }}>Bitte Aktion auswählen...</option>
            @foreach($controllerActions as $action => $label)
                <option value="{{ $action }}" {{ old('controller_action', $notificationRule->controller_action ?? '') == $action ? 'selected' : '' }}>
                    {{ $label }} ({{ $action }})
                </option>
            @endforeach
        </select>
        @error('controller_action')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Target Type Dropdown --}}
    <div class="form-group">
        <label for="target_type">Benachrichtigungsziel (Typ) <span class="text-danger">*</span></label>
        <select name="target_type" id="target_type" class="form-control select2 @error('target_type') is-invalid @enderror" required>
            <option value="" disabled {{ old('target_type', $notificationRule->target_type ?? '') == '' ? 'selected' : '' }}>Bitte Typ auswählen...</option>
            @foreach($targetTypes as $type => $label)
                <option value="{{ $type }}" {{ old('target_type', $notificationRule->target_type ?? '') == $type ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('target_type')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Target Identifier Dropdown (wird dynamisch befüllt) --}}
    <div class="form-group">
        <label for="target_identifier">Ziel-Identifier <span class="text-danger">*</span></label>
        {{-- Das Select-Feld wird jetzt nur noch vom JavaScript befüllt --}}
        <select name="target_identifier" id="target_identifier" class="form-control select2 @error('target_identifier') is-invalid @enderror" required>
            {{-- PHP-Logik zur Vorauswahl des richtigen Labels (korrekt) --}}
             @php
                $currentIdentifier = old('target_identifier', $notificationRule->target_identifier ?? null);
                $currentType = old('target_type', $notificationRule->target_type ?? null);
                $identifierLabel = $currentIdentifier;

                if ($currentIdentifier && $currentType) {
                    if ($currentType === 'user' && isset($availableIdentifiers['Benutzer'][$currentIdentifier])) {
                        $identifierLabel = $availableIdentifiers['Benutzer'][$currentIdentifier] . ' (ID: ' . $currentIdentifier . ')';
                    } elseif ($currentType === 'role' && isset($availableIdentifiers['Rollen'][$currentIdentifier])) {
                        $identifierLabel = $availableIdentifiers['Rollen'][$currentIdentifier];
                    } elseif ($currentType === 'permission' && isset($availableIdentifiers['Berechtigungen'][$currentIdentifier])) {
                        $identifierLabel = $availableIdentifiers['Berechtigungen'][$currentIdentifier];
                    } elseif ($currentType === 'user' && $currentIdentifier === 'triggering_user' && isset($availableIdentifiers['Spezifisch']['triggering_user'])) {
                        $identifierLabel = $availableIdentifiers['Spezifisch']['triggering_user'];
                    }
                }
             @endphp
             {{-- Zeige die initial ausgewählte Option oder einen Platzhalter --}}
             @if($currentIdentifier)
                 <option value="{{ $currentIdentifier }}" selected>{{ $identifierLabel }}</option>
             @else
                 <option value="" disabled selected>Zuerst Typ auswählen...</option>
            @endif
        </select>
        @error('target_identifier')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Description Textarea --}}
    <div class="form-group">
        <label for="description">Beschreibung (Optional)</label>
        <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" rows="3" placeholder="Kurze Beschreibung, wofür diese Regel dient...">{{ old('description', $notificationRule->description ?? '') }}</textarea>
        @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Is Active Checkbox --}}
    <div class="form-group">
        <div class="custom-control custom-switch">
            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ old('is_active', $notificationRule->is_active ?? true) ? 'checked' : '' }}>
            <label class="custom-control-label" for="is_active">Regel Aktiv</label>
        </div>
        <small class="form-text text-muted">Nur aktive Regeln werden Benachrichtigungen auslösen.</small>
    </div>

</div>

<div class="card-footer">
    <a href="{{ route('admin.notification-rules.index') }}" class="btn btn-secondary">
        <i class="fas fa-times"></i> Abbrechen
    </a>
    <button type="submit" class="btn btn-primary float-right">
        <i class="fas fa-save"></i> Regel Speichern
    </button>
</div>

{{-- JavaScript für Select2 und dynamische Identifier --}}
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        // Initialisiere alle Select2-Felder mit Bootstrap 4 Theme
        $('.select2').select2({
            theme: 'bootstrap4',
            placeholder: $(this).data('placeholder') || 'Bitte auswählen...',
            allowClear: Boolean($(this).data('allow-clear')),
        });

        // Datenquelle für die Identifier (aus PHP übergeben)
        const identifiers = @json($availableIdentifiers);
        // KORREKTUR: Hole den initialen Wert direkt aus dem Select-Feld,
        // da das PHP oben die Option bereits korrekt rendert.
        let currentIdentifierValue = $('#target_identifier').val();


        // Funktion zum Aktualisieren der Identifier-Optionen
        function updateIdentifierOptions(selectedType) {
            const $identifierSelect = $('#target_identifier');
            // Behalte den aktuell ausgewählten Wert, wenn möglich
            const previouslySelectedValue = $identifierSelect.val();
            $identifierSelect.empty(); // Leere aktuelle Optionen

            let optionsData = [];
            let placeholderText = 'Bitte auswählen...';
            let enableClear = true;

            switch (selectedType) {
                case 'role':
                    optionsData = identifiers['Rollen'] || {};
                    placeholderText = 'Rolle auswählen...';
                    break;
                case 'permission':
                    optionsData = identifiers['Berechtigungen'] || {};
                     placeholderText = 'Berechtigung auswählen...';
                    break;
                case 'user':
                    // Kombiniere Benutzer und spezielle Identifier
                    optionsData = { ...(identifiers['Benutzer'] || {}), ...(identifiers['Spezifisch'] || {}) };
                    placeholderText = 'Benutzer oder spezifisches Ziel auswählen...';
                    break;
                default:
                    placeholderText = 'Zuerst Typ auswählen...';
                    enableClear = false; // Kein Leeren erlauben, wenn Typ nicht gewählt
            }

            // Füge Platzhalter hinzu (wird von Select2 überschrieben, aber gut für Fallback)
             $identifierSelect.append(new Option(placeholderText, "", true, true)).prop('disabled', $.isEmptyObject(optionsData) && selectedType === '');


            // Füge die neuen Optionen hinzu (gruppiert für Benutzer/Spezifisch)
             if (selectedType === 'user') {
                 // Benutzer-Gruppe
                 if (!$.isEmptyObject(identifiers['Benutzer'])) {
                     const $userGroup = $('<optgroup label="Benutzer"></optgroup>');
                     $.each(identifiers['Benutzer'], function(id, name) {
                         $userGroup.append(new Option(name + ' (ID: ' + id + ')', id, false, false)); // Vorauswahl wird später gesetzt
                     });
                     $identifierSelect.append($userGroup);
                 }
                  // Spezifisch-Gruppe
                 if (!$.isEmptyObject(identifiers['Spezifisch'])) {
                     const $specificGroup = $('<optgroup label="Spezifisch"></optgroup>');
                     $.each(identifiers['Spezifisch'], function(key, label) {
                         $specificGroup.append(new Option(label, key, false, false)); // Vorauswahl wird später gesetzt
                     });
                     $identifierSelect.append($specificGroup);
                 }

             } else {
                 // Füge Optionen für Rollen und Berechtigungen hinzu
                 $.each(optionsData, function(key, value) {
                     const optionValue = (selectedType === 'user') ? key : value;
                     const optionText = value;
                     $identifierSelect.append(new Option(optionText, optionValue, false, false)); // Vorauswahl wird später gesetzt
                 });
             }

            // Select2 neu initialisieren oder aktualisieren
            $identifierSelect.select2({
                 theme: 'bootstrap4',
                 placeholder: placeholderText,
                 allowClear: enableClear,
            });

             // Versuche, den vorherigen Wert oder den 'old'/'current' Wert wieder auszuwählen
             let valueToSelect = previouslySelectedValue;
             // Wenn der Typ geändert wurde ODER kein Wert vorher selektiert war, prüfe currentIdentifierValue
             if ($('#target_type').val() === selectedType && (!valueToSelect || valueToSelect === '')) {
                 valueToSelect = currentIdentifierValue;
             }
             // Setze den Wert, wenn er in den neuen Optionen existiert
             if (valueToSelect && $identifierSelect.find("option[value='" + valueToSelect + "']").length) {
                 $identifierSelect.val(valueToSelect).trigger('change.select2'); // Verwende change.select2, um Events auszulösen
             } else {
                 // Wenn der alte Wert nicht mehr gültig ist, setze auf leer zurück
                 $identifierSelect.val(null).trigger('change.select2');
                 // Aktualisiere currentIdentifierValue, damit es beim nächsten Typwechsel nicht stört
                 currentIdentifierValue = null;
             }

        }

        // Event Listener für Änderungen am Target Type Dropdown
        $('#target_type').on('change', function() {
             // Beim Ändern des Typs setzen wir currentIdentifierValue zurück,
             // es sei denn, es ist der initiale Ladevorgang.
             // Wir holen den Wert *bevor* wir updaten.
            currentIdentifierValue = $('#target_identifier').val();
            updateIdentifierOptions($(this).val());
        });

        // Initialisiere die Identifier-Optionen beim Laden der Seite
        const initialType = $('#target_type').val();
        if (initialType) {
            updateIdentifierOptions(initialType);
        } else {
            // Initial deaktivert, wenn kein Typ gewählt ist
             $('#target_identifier').prop('disabled', true).select2({
                 theme: 'bootstrap4',
                 placeholder: 'Zuerst Typ auswählen...',
                 allowClear: false, // Hier kein Clear erlauben
             });
        }
    });
</script>
@endpush

