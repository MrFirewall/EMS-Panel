{{-- Dieses Partial wird von create.blade.php und edit.blade.php verwendet --}}
{{-- Die Variable $notificationRule ist in 'edit' gesetzt, in 'create' ist sie null --}}

{{-- Select2 CSS wird jetzt im Hauptlayout geladen --}}
@push('styles')
    {{-- Theme für Bootstrap 4 --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css">
    <style>
        /* NEU: Blendet ausgewählte Optionen im Dropdown-Menü aus (funktioniert für Multi-Select) */
        .select2-results__option[aria-selected="true"] {
            display: none;
        }

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
        <label for="controller_action">Auslösende Aktion(en) <span class="text-danger">*</span></label>
        {{-- NEU: 'multiple' und 'name="...[]"' --}}
        <select name="controller_action[]" id="controller_action" class="form-control select2 @error('controller_action') is-invalid @enderror" required multiple>
            <option value="" disabled>Bitte Aktion(en) auswählen...</option>
            @php
                // GEÄNDERT: Hole das Array der alten/gespeicherten Werte
                $currentActions = old('controller_action', $notificationRule->controller_action ?? []);
            @endphp
            @foreach($controllerActions as $action => $label)
                {{-- GEÄNDERT: Prüfe mit in_array --}}
                <option value="{{ $action }}" {{ in_array($action, $currentActions) ? 'selected' : '' }}>
                    {{ $label }} ({{ $action }})
                </option>
            @endforeach
        </select>
        @error('controller_action')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        @error('controller_action.*') {{-- NEU: Fehler für Array-Einträge --}}
            <div class="invalid-feedback d-block">{{ $message }}</div>
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
        {{-- NEU: 'multiple' und 'name="...[]"' --}}
        <select name="target_identifier[]" id="target_identifier" class="form-control select2 @error('target_identifier') is-invalid @enderror" required multiple>

            {{-- GEÄNDERT: PHP-Logik zur Vorauswahl für Multi-Select --}}
            @php
                $currentIdentifiers = old('target_identifier', $notificationRule->target_identifier ?? []);
                if (!is_array($currentIdentifiers)) { // Fallback, falls es noch ein String ist
                    $currentIdentifiers = $currentIdentifiers ? [$currentIdentifiers] : [];
                }
                $currentType = old('target_type', $notificationRule->target_type ?? null);
            @endphp

            @foreach($currentIdentifiers as $currentIdentifier)
                @php
                    // Diese Logik rendert das gespeicherte Label
                    $identifierLabel = $currentIdentifier;
                    if ($currentIdentifier && $currentType) {
                        if ($currentType === 'user' && isset($availableIdentifiers['Benutzer'][$currentIdentifier])) {
                            $identifierLabel = $availableIdentifiers['Benutzer'][$currentIdentifier] . ' (ID: ' . $currentIdentifier . ')';
                        } elseif ($currentType === 'role' && isset($availableIdentifiers['Rollen'][$currentIdentifier])) {
                            $identifierLabel = $availableIdentifiers['Rollen'][$currentIdentifier];
                        } elseif ($currentType === 'permission' && isset($availableIdentifiers['Berechtigungen'][$currentIdentifier])) {
                            $identifierLabel = $availableIdentifiers['Berechtigungen'][$currentIdentifier];
                        } elseif (isset($availableIdentifiers['Spezifisch'][$currentIdentifier])) {
                            $identifierLabel = $availableIdentifiers['Spezifisch'][$currentIdentifier];
                        }
                    }
                @endphp
                <option value="{{ $currentIdentifier }}" selected>{{ $identifierLabel }}</option>
            @endforeach

            @if(empty($currentIdentifiers))
                <option value="" disabled>Zuerst Typ auswählen...</option>
            @endif
        </select>
        @error('target_identifier')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        @error('target_identifier.*') {{-- NEU: Fehler für Array-Einträge --}}
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    {{-- Description Textarea --}}
    <div class="form-group">
        {{-- KORREKTUR: Label for und Name/ID des Textarea --}}
        <label for="event_description">Beschreibung (Optional)</label>
        <textarea name="event_description" id="event_description" class="form-control @error('event_description') is-invalid @enderror" rows="3" placeholder="Kurze Beschreibung, wofür diese Regel dient...">{{ old('event_description', $notificationRule->event_description ?? '') }}</textarea>
        @error('event_description')
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
{{-- Das Select2 JS wird jetzt im Hauptlayout geladen --}}
@push('scripts')
<script>
    $(document).ready(function() {
        // Initialisiere alle Select2-Felder mit Bootstrap 4 Theme
        if (typeof $.fn.select2 === 'function') {
            $('.select2').select2({
                theme: 'bootstrap4',
                placeholder: $(this).data('placeholder') || 'Bitte auswählen...',
                allowClear: Boolean($(this).data('allow-clear')),
            });
        } else {
            console.error("Select2 wurde nicht gefunden. Stelle sicher, dass es im Hauptlayout geladen wird.");
        }

        const identifiers = @json($availableIdentifiers);
        
        // --- GEÄNDERTE LOGIK ---
        
        // Speichere den Typ und die Werte, die beim Laden der Seite aktiv waren
        // (entweder aus der DB oder aus 'old()' bei einem Validierungsfehler)
        const initialType = $('#target_type').val();
        const initialIdentifiers = @json($currentIdentifiers); // Nutzt die PHP-Variable von oben

        function updateIdentifierOptions(selectedType) {
            const $identifierSelect = $('#target_identifier');
            
            // Behalte die aktuell ausgewählten Werte nur, wenn der Typ sich NICHT geändert hat
            // (Diese Zeile muss VOR .empty() stehen)
            let valuesToRestore = (selectedType === initialType) ? initialIdentifiers : [];

            $identifierSelect.empty(); 

            let placeholderText = 'Bitte auswählen...';
            let enableClear = true;

            switch (selectedType) {
                case 'role':
                    placeholderText = 'Rolle(n) auswählen...';
                    $.each(identifiers['Rollen'] || {}, function(key, value) {
                        // Der 3. Parameter ist "selected", der 4. "disabled"
                        $identifierSelect.append(new Option(value, key, false, false));
                    });
                    break;
                case 'permission':
                    placeholderText = 'Berechtigung(en) auswählen...';
                    $.each(identifiers['Berechtigungen'] || {}, function(key, value) {
                        $identifierSelect.append(new Option(value, key, false, false));
                    });
                    break;
                case 'user':
                    placeholderText = 'Benutzer oder spezifisches Ziel auswählen...';
                    // Optgroup für Benutzer
                    if (!$.isEmptyObject(identifiers['Benutzer'])) {
                        const $userGroup = $('<optgroup label="Benutzer"></optgroup>');
                        $.each(identifiers['Benutzer'], function(id, name) {
                            $userGroup.append(new Option(name + ' (ID: ' + id + ')', id, false, false));
                        });
                        $identifierSelect.append($userGroup);
                    }
                    // Optgroup für Spezifisch
                    if (!$.isEmptyObject(identifiers['Spezifisch'])) {
                        const $specificGroup = $('<optgroup label="Spezifisch"></optgroup>');
                        $.each(identifiers['Spezifisch'], function(key, label) {
                            $specificGroup.append(new Option(label, key, false, false));
                        });
                        $identifierSelect.append($specificGroup);
                    }
                    break;
                default:
                    placeholderText = 'Zuerst Typ auswählen...';
                    enableClear = false;
                    $identifierSelect.prop('disabled', true);
            }

            if (selectedType) {
                $identifierSelect.prop('disabled', false);
            }

            // Initialisiere Select2 für das Identifier-Feld neu
            if (typeof $.fn.select2 === 'function') {
                $identifierSelect.select2({
                    theme: 'bootstrap4',
                    placeholder: placeholderText,
                    allowClear: enableClear,
                });
            }
            
            // Stelle die alten/initialen Werte (als Array) wieder her,
            // aber nur, wenn der Typ dem initialen Typ entspricht.
            if (valuesToRestore.length > 0) {
                $identifierSelect.val(valuesToRestore).trigger('change.select2');
            } else {
                $identifierSelect.val(null).trigger('change.select2');
            }
        }

        // Event Listener für die Typ-Änderung
        $('#target_type').on('change', function() {
            // Wenn der Typ geändert wird, werden die Identifier-Werte durch 
            // die updateIdentifierOptions-Funktion automatisch zurückgesetzt.
            updateIdentifierOptions($(this).val());
        });

        // Führe die Funktion beim Laden der Seite aus, um das Feld zu befüllen
        updateIdentifierOptions(initialType);
        
        // --- ENDE GEÄNDERTE LOGIK ---
    });
</script>
@endpush