
<div class="card-body">
    {{-- Controller Action Dropdown --}}
    <div class="form-group">
        <label for="controller_action">Auslösende Aktion(en) <span class="text-danger">*</span></label>
        {{-- HINWEIS: Klasse von 'select2' zu 'select2-multihide' geändert --}}
        <select name="controller_action[]" id="controller_action" class="form-control select2-multihide @error('controller_action') is-invalid @enderror" required multiple>
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
        {{-- HINWEIS: Klasse von 'select2' zu 'select2-single' geändert --}}
        <select name="target_type" id="target_type" class="form-control select2-single @error('target_type') is-invalid @enderror" required>
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
        {{-- HINWEIS: Klasse von 'select2' zu 'select2-multihide' geändert --}}
        <select name="target_identifier[]" id="target_identifier" class="form-control select2-multihide @error('target_identifier') is-invalid @enderror" required multiple>

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
    // FÜGE DIE VORLAGEN-FUNKTION AUS DEINEM BEISPIEL HINZU
    function hideSelectedTemplate(data) {
        if (data.selected) {
            return null;
        }
        return data.text;
    }

    // NEUE FUNKTION: Erzwingt die Neuberechnung der Höhe
    function fixSelect2Height($select) {
        // Öffne und schließe Select2 sofort, um die interne Höhenberechnung zu triggern.
        // Führt oft zu einem besseren Rendering der Tags.
        $select.select2('open').select2('close');
    }

    $(document).ready(function() {
        
        // 1. Initialisiere Single-Select-Felder
        if (typeof $.fn.select2 === 'function') {
            $('.select2-single').select2({
                theme: 'bootstrap4',
                placeholder: 'Bitte Typ auswählen...',
                allowClear: false,
            });

            // 2. Initialisiere Multi-Select-Feld 'controller_action'
            const $controllerActionSelect = $('#controller_action');
            $controllerActionSelect.select2({
                theme: 'bootstrap4',
                placeholder: 'Bitte Aktion(en) auswählen...',
                allowClear: true,
                templateResult: hideSelectedTemplate
            });
            // FIX: Zwinge Select2, die Höhe sofort nach der Initialisierung neu zu berechnen
            fixSelect2Height($controllerActionSelect);
        } else {
            console.error("Select2 wurde nicht gefunden. Stelle sicher, dass es im Hauptlayout geladen wird.");
        }

        const identifiers = @json($availableIdentifiers);
        
        // --- LOGIK FÜR DYNAMISCHES FELD target_identifier ---
        
        const initialType = $('#target_type').val();
        const initialIdentifiers = @json($currentIdentifiers); 

        function updateIdentifierOptions(selectedType) {
            const $identifierSelect = $('#target_identifier');
            
            let valuesToRestore = (selectedType === initialType) ? initialIdentifiers : [];

            $identifierSelect.empty(); 
            // ... (Rest der Logik zum Befüllen der Optionen bleibt gleich) ...
            let placeholderText = 'Bitte auswählen...';
            let enableClear = true;

            switch (selectedType) {
                case 'role':
                    placeholderText = 'Rolle(n) auswählen...';
                    $.each(identifiers['Rollen'] || {}, function(key, value) {
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
                    templateResult: hideSelectedTemplate
                });
            }
            
            // Stelle die alten/initialen Werte (als Array) wieder her.
            if (valuesToRestore.length > 0) {
                $identifierSelect.val(valuesToRestore).trigger('change.select2');
            } else {
                $identifierSelect.val(null).trigger('change.select2');
            }

            // FIX: Zwinge Select2, die Höhe sofort nach dem Befüllen neu zu berechnen
            if (valuesToRestore.length > 0) {
                fixSelect2Height($identifierSelect);
            }
        }

        // Event Listener für die Typ-Änderung
        $('#target_type').on('change', function() {
            updateIdentifierOptions($(this).val());
        });

        // Führe die Funktion beim Laden der Seite aus, um das Feld zu befüllen
        updateIdentifierOptions(initialType);
        
        // --- ENDE LOGIK FÜR DYNAMISCHES FELD ---
    });
</script>
@endpush