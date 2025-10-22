@csrf
<div class="card-body">
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="controller_action">Auslösende Aktion <span class="text-danger">*</span></label>
                <select name="controller_action" id="controller_action" class="form-control select2 @error('controller_action') is-invalid @enderror" required>
                    <option value="">Bitte wählen...</option>
                    @foreach($controllerActions as $action => $description)
                        <option value="{{ $action }}" {{ old('controller_action', $notificationRule->controller_action ?? '') == $action ? 'selected' : '' }}>
                            {{ $description }}
                        </option>
                    @endforeach
                </select>
                @error('controller_action') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="col-md-6">
             <div class="form-group">
                <label for="event_description">Beschreibung (für Übersicht) <span class="text-danger">*</span></label>
                <input type="text" name="event_description" id="event_description" class="form-control @error('event_description') is-invalid @enderror"
                       value="{{ old('event_description', $notificationRule->event_description ?? '') }}" required placeholder="z.B. Admins bei neuem Antrag benachrichtigen">
                 @error('event_description') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>


    <div class="row">
         <div class="col-md-6">
            <div class="form-group">
                <label for="target_type">Benachrichtige basierend auf <span class="text-danger">*</span></label>
                <select name="target_type" id="target_type" class="form-control @error('target_type') is-invalid @enderror" required>
                     <option value="">Bitte wählen...</option>
                    @foreach($targetTypes as $type => $label)
                        <option value="{{ $type }}" {{ old('target_type', $notificationRule->target_type ?? '') == $type ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                 @error('target_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="col-md-6">
            {{-- Dynamische Felder für target_identifier --}}
            <div class="form-group target-identifier-group" id="target-role-group" style="display: none;">
                <label for="target_identifier_role">Rolle <span class="text-danger">*</span></label>
                <select name="target_identifier_role" id="target_identifier_role" class="form-control select2 @error('target_identifier') is-invalid @enderror">
                    <option value="">Bitte wählen...</option>
                     @foreach($roles as $roleName => $displayName) {{-- Annahme: $roles ist 'name' => 'name' --}}
                         <option value="{{ $roleName }}" {{ (old('target_type', $notificationRule->target_type ?? '') == 'role' && old('target_identifier', $notificationRule->target_identifier ?? '') == $roleName) ? 'selected' : '' }}>
                             {{ $displayName }}
                         </option>
                     @endforeach
                </select>
            </div>

            <div class="form-group target-identifier-group" id="target-permission-group" style="display: none;">
                 <label for="target_identifier_permission">Berechtigung <span class="text-danger">*</span></label>
                 <select name="target_identifier_permission" id="target_identifier_permission" class="form-control select2 @error('target_identifier') is-invalid @enderror">
                     <option value="">Bitte wählen...</option>
                      @foreach($permissions as $permissionName => $displayName) {{-- Annahme: $permissions ist 'name' => 'name' --}}
                          <option value="{{ $permissionName }}" {{ (old('target_type', $notificationRule->target_type ?? '') == 'permission' && old('target_identifier', $notificationRule->target_identifier ?? '') == $permissionName) ? 'selected' : '' }}>
                              {{ $displayName }}
                          </option>
                      @endforeach
                 </select>
            </div>

             <div class="form-group target-identifier-group" id="target-user-group" style="display: none;">
                 <label for="target_identifier_user">Benutzer <span class="text-danger">*</span></label>
                 <select name="target_identifier_user" id="target_identifier_user" class="form-control select2 @error('target_identifier') is-invalid @enderror" data-placeholder="Benutzer auswählen...">
                     <option value="">Bitte wählen...</option>
                      @foreach($users as $userId => $userName)
                          <option value="{{ $userId }}" {{ (old('target_type', $notificationRule->target_type ?? '') == 'user' && old('target_identifier', $notificationRule->target_identifier ?? '') == $userId) ? 'selected' : '' }}>
                              {{ $userName }}
                          </option>
                      @endforeach
                 </select>
             </div>
            {{-- Verstecktes Feld, das per JS befüllt wird --}}
            <input type="hidden" name="target_identifier" id="target_identifier" value="{{ old('target_identifier', $notificationRule->target_identifier ?? '') }}">
            @error('target_identifier') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>
    </div>


    <div class="form-group">
         <div class="custom-control custom-switch">
             <input type="hidden" name="is_active" value="0"> {{-- Standardwert 0 --}}
             <input type="checkbox" name="is_active" class="custom-control-input" id="is_active" value="1"
                    {{ old('is_active', $notificationRule->is_active ?? true) ? 'checked' : '' }}>
             <label class="custom-control-label" for="is_active">Regel ist aktiv</label>
         </div>
     </div>
</div>

<div class="card-footer">
    <a href="{{ route('admin.notification-rules.index') }}" class="btn btn-secondary"><i class="fas fa-times"></i> Abbrechen</a>
    <button type="submit" class="btn btn-primary float-right"><i class="fas fa-save"></i> Speichern</button>
</div>

{{-- Dieses Script wird per @push('scripts') in create/edit eingefügt --}}
@push('scripts')
<script>
    $(document).ready(function() {
        // Select2 initialisieren
        $('.select2').select2({
            theme: 'bootstrap4',
            width: '100%' // Wichtig für Bootstrap 4 Layout
        });

        function toggleTargetIdentifierFields() {
            var selectedType = $('#target_type').val();
            // Alle Gruppen ausblenden und required entfernen
            $('.target-identifier-group').hide();
            $('.target-identifier-group select').prop('required', false);

            $('#target_identifier').val(''); // Reset hidden field
            var activeSelect = null;
            var targetGroup = null;

            if (selectedType === 'role') {
                targetGroup = $('#target-role-group');
                activeSelect = $('#target_identifier_role');
            } else if (selectedType === 'permission') {
                targetGroup = $('#target-permission-group');
                activeSelect = $('#target_identifier_permission');
            } else if (selectedType === 'user') {
                targetGroup = $('#target-user-group');
                activeSelect = $('#target_identifier_user');
            }

            // Zielgruppe einblenden und required setzen
            if(targetGroup && activeSelect) {
                targetGroup.show();
                activeSelect.prop('required', true);
                 // Beim Laden oder Ändern: Wert aus dem (jetzt sichtbaren) Select ins Hidden Field kopieren
                $('#target_identifier').val(activeSelect.val());
            }
        }

        // Beim Ändern des Typs (Rolle/Permission/User)
        $('#target_type').on('change', function() {
            toggleTargetIdentifierFields();
        });

         // Beim Ändern des spezifischen Auswahlfeldes (Rolle, Berechtigung, User)
         $('#target_identifier_role, #target_identifier_permission, #target_identifier_user').on('change', function() {
             var selectedValue = $(this).val();
             $('#target_identifier').val(selectedValue); // Wert ins Hidden Field kopieren
         });

        // Initial aufrufen, um die richtigen Felder beim Laden (z.B. Editieren) anzuzeigen
        toggleTargetIdentifierFields();
    });
</script>
@endpush
