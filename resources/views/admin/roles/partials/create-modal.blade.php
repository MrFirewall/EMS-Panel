@php
    // Überprüfe, ob Validierungsfehler für das Feld 'name' vorliegen.
    // Wir verwenden die klassische $errors->has()-Methode für BS4/AdminLTE.
    $hasError = $errors->has('name');
@endphp

<div class="modal fade" id="createRoleModal" tabindex="-1" role="dialog" aria-labelledby="createRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="card card-primary card-outline mb-0"> {{-- AdminLTE Card im Modal --}}
                <form action="{{ route('admin.roles.store') }}" method="POST">
                    @csrf
                    <div class="modal-header bg-success"> {{-- Keine text-white nötig, da AdminLTE Dark Mode das handelt --}}
                        <h5 class="modal-title" id="createRoleModalLabel">Neue Rolle erstellen</h5>
                        {{-- KORREKTUR: BS4/AdminLTE Close Button --}}
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small">
                            Rollen werden im System als Slugs gespeichert (z.B. "chief"). Bitte nur Kleinbuchstaben und Bindestriche verwenden.
                        </p>
                        {{-- BS5 mb-3 ersetzt durch BS4 form-group --}}
                        <div class="form-group">
                            {{-- BS5 form-label entfernt --}}
                            <label for="new_role_name">Rollenname (Slug)</label>
                            <input type="text" 
                                    class="form-control @error('name') is-invalid @enderror" 
                                    id="new_role_name" 
                                    name="name" 
                                    value="{{ old('name') }}" 
                                    required 
                                    placeholder="z.B. neuer-ausbilder">
                            
                            {{-- KORREKTUR: AdminLTE/BS4 invalid-feedback ist notwendig --}}
                            @error('name')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat" data-dismiss="modal">Abbrechen</button>
                        {{-- KORREKTUR: btn-flat für AdminLTE Stil --}}
                        <button type="submit" class="btn btn-success btn-flat">
                            <i class="fas fa-plus me-1"></i> Rolle erstellen
                        </button>
                    </div>
                </form>
            </div> {{-- /.card --}}
        </div>
    </div>
</div>

{{-- JavaScript zur automatischen Öffnung des Modals bei Validierungsfehlern --}}
{{-- KORREKTUR: Die Validierungsprüfung muss korrekt mit der $hasError Variable arbeiten --}}
@if ($hasError)
<script>
    // Nutzt jQuery (in AdminLTE enthalten) für die Modalsteuerung (BS4)
    $(document).ready(function() {
        $('#createRoleModal').modal('show');
    });
</script>
@endif