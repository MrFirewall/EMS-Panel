@php
    // Fehler-Bag für dieses Modal prüfen
    $modalErrors = $errors->createRole ?? new \Illuminate\Support\MessageBag;
@endphp

<div class="modal fade" id="createRoleModal" tabindex="-1" role="dialog" aria-labelledby="createRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="card card-success card-outline mb-0"> {{-- Farbe geändert --}}
                <form action="{{ route('admin.roles.store') }}" method="POST">
                    @csrf
                    <div class="modal-header bg-success">
                        <h5 class="modal-title" id="createRoleModalLabel">Neue Rolle erstellen</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small">
                            Rollen werden als Slugs gespeichert (nur Kleinbuchstaben, Zahlen, Bindestriche).
                        </p>
                        
                        {{-- Rollenname --}}
                        <div class="form-group">
                            <label for="new_role_name">Rollenname (Slug)</label>
                            <input type="text" 
                                   class="form-control {{ $modalErrors->has('name') ? 'is-invalid' : '' }}" 
                                   id="new_role_name" 
                                   name="name" 
                                   value="{{ old('name') }}" 
                                   required 
                                   placeholder="z.B. neuer-ausbilder">
                            @if ($modalErrors->has('name'))
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $modalErrors->first('name') }}</strong>
                                </span>
                            @endif
                        </div>

                        {{-- Rollentyp --}}
                        <div class="form-group">
                            <label>Rollentyp</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="role_type" id="create_type_rank" value="rank" {{ old('role_type') == 'rank' ? 'checked' : '' }}>
                                <label class="form-check-label" for="create_type_rank">Rang</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="role_type" id="create_type_department" value="department" {{ old('role_type') == 'department' ? 'checked' : '' }}>
                                <label class="form-check-label" for="create_type_department">Abteilungsrolle</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="role_type" id="create_type_other" value="other" {{ old('role_type', 'other') == 'other' ? 'checked' : '' }}> {{-- Standard: other --}}
                                <label class="form-check-label" for="create_type_other">Andere Rolle</label>
                            </div>
                             @if ($modalErrors->has('role_type'))
                                <div class="text-danger small mt-1">{{ $modalErrors->first('role_type') }}</div>
                            @endif
                        </div>
                        
                        {{-- Department Auswahl (Konditional) --}}
                        <div class="form-group" id="create_department_select_group" style="{{ old('role_type') == 'department' ? '' : 'display: none;' }}">
                             <label for="create_department_id">Zugehörige Abteilung</label>
                             <select name="department_id" id="create_department_id" class="form-control {{ $modalErrors->has('department_id') ? 'is-invalid' : '' }}">
                                 <option value="">Bitte Abteilung wählen...</option>
                                 @foreach($allDepartments ?? [] as $department) {{-- Prüfe ob Variable existiert --}}
                                     <option value="{{ $department->id }}" {{ old('department_id') == $department->id ? 'selected' : '' }}>
                                         {{ $department->name }}
                                     </option>
                                 @endforeach
                             </select>
                             {{-- Optional: Button zum Erstellen einer neuen Abteilung direkt von hier --}}
                             {{-- <button type="button" class="btn btn-xs btn-link" data-toggle="modal" data-target="#createDepartmentModal" data-dismiss="modal">Neue Abteilung erstellen?</button> --}}
                              @if ($modalErrors->has('department_id'))
                                <div class="invalid-feedback">{{ $modalErrors->first('department_id') }}</div>
                            @endif
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat" data-dismiss="modal">Abbrechen</button>
                        <button type="submit" class="btn btn-success btn-flat">
                            <i class="fas fa-plus me-1"></i> Rolle erstellen
                        </button>
                    </div>
                </form>
            </div> 
        </div>
    </div>
</div>