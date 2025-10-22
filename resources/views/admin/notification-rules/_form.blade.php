{{-- Dieses Partial wird von create.blade.php und edit.blade.php verwendet --}}
{{-- Die Variable $notificationRule ist in 'edit' gesetzt, in 'create' ist sie null --}}

{{-- Select2 CSS wird jetzt im Hauptlayout geladen --}}
@push('styles')
    {{-- Theme für Bootstrap 4 --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css">
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
{{-- <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script> --}} {{-- Wird im Hauptlayout geladen --}}
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
        let currentIdentifierValue = $('#target_identifier').val();

        function updateIdentifierOptions(selectedType) {
            const $identifierSelect = $('#target_identifier');
            const previouslySelectedValue = $identifierSelect.val();
            $identifierSelect.empty();

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
                    optionsData = { ...(identifiers['Benutzer'] || {}), ...(identifiers['Spezifisch'] || {}) };
                    placeholderText = 'Benutzer oder spezifisches Ziel auswählen...';
                    break;
                default:
                    placeholderText = 'Zuerst Typ auswählen...';
                    enableClear = false;
            }

             $identifierSelect.append(new Option(placeholderText, "", true, true)).prop('disabled', $.isEmptyObject(optionsData) && selectedType === '');

             if (selectedType === 'user') {
                 if (!$.isEmptyObject(identifiers['Benutzer'])) {
                     const $userGroup = $('<optgroup label="Benutzer"></optgroup>');
                     $.each(identifiers['Benutzer'], function(id, name) {
                         $userGroup.append(new Option(name + ' (ID: ' + id + ')', id, false, false));
                     });
                     $identifierSelect.append($userGroup);
                 }
                 if (!$.isEmptyObject(identifiers['Spezifisch'])) {
                     const $specificGroup = $('<optgroup label="Spezifisch"></optgroup>');
                     $.each(identifiers['Spezifisch'], function(key, label) {
                         $specificGroup.append(new Option(label, key, false, false));
                     });
                     $identifierSelect.append($specificGroup);
                 }
             } else {
                 $.each(optionsData, function(key, value) {
                     const optionValue = (selectedType === 'user') ? key : value;
                     const optionText = value;
                     $identifierSelect.append(new Option(optionText, optionValue, false, false));
                 });
             }

            if (typeof $.fn.select2 === 'function') {
                $identifierSelect.select2({
                    theme: 'bootstrap4',
                    placeholder: placeholderText,
                    allowClear: enableClear,
                });
            }

             let valueToSelect = previouslySelectedValue;
             if ($('#target_type').val() === selectedType && (!valueToSelect || valueToSelect === '')) {
                 valueToSelect = currentIdentifierValue;
             }
             if (valueToSelect && $identifierSelect.find("option[value='" + valueToSelect + "']").length) {
                 $identifierSelect.val(valueToSelect).trigger('change.select2');
             } else {
                 $identifierSelect.val(null).trigger('change.select2');
                 currentIdentifierValue = null;
             }
        }

        $('#target_type').on('change', function() {
            currentIdentifierValue = $('#target_identifier').val();
            updateIdentifierOptions($(this).val());
        });

        const initialType = $('#target_type').val();
        if (initialType) {
            updateIdentifierOptions(initialType);
        } else {
            if (typeof $.fn.select2 === 'function') {
                $('#target_identifier').prop('disabled', true).select2({
                    theme: 'bootstrap4',
                    placeholder: 'Zuerst Typ auswählen...',
                    allowClear: false,
                });
            }
        }
    });
</script>
@endpush

