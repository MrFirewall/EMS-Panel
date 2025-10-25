@extends('layouts.app')

@section('title', 'Mitarbeiter bearbeiten: ' . $user->name)

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <h1 class="m-0">Mitarbeiter bearbeiten: {{ $user->name }}</h1>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.users.update', $user) }}">
        @csrf
        @method('PUT')

        {{-- Stammdaten-Karte --}}
        <div class="card card-outline card-primary mb-4">
            <div class="card-header"><h3 class="card-title">Stammdaten</h3></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="name">Mitarbeiter Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" id="name" value="{{ old('name', $user->name) }}" required>
                            @error('name')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="personal_number">Personalnummer</label>
                            <select name="personal_number" id="personal_number" class="form-control @error('personal_number') is-invalid @enderror" required>
                                <option value="">Bitte wählen...</option>
                                {{-- Aktuelle Nummer immer anbieten --}}
                                @if($user->personal_number)
                                    <option value="{{ $user->personal_number }}" selected>{{ $user->personal_number }} (Aktuell)</option>
                                @endif
                                {{-- Verfügbare Nummern hinzufügen (außer der aktuellen) --}}
                                @foreach($availablePersonalNumbers as $number)
                                    @if($number != $user->personal_number)
                                      <option value="{{ $number }}" @if(old('personal_number') == $number) selected @endif>{{ $number }}</option>
                                    @endif
                                @endforeach
                            </select>
                            @error('personal_number')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="employee_id">Mitarbeiter ID</label>
                            <input type="text" class="form-control @error('employee_id') is-invalid @enderror" name="employee_id" id="employee_id" value="{{ old('employee_id', $user->employee_id) }}">
                            @error('employee_id')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="email">E-Mail</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" id="email" value="{{ old('email', $user->email) }}">
                            @error('email')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="birthday">Geburtstag</label>
                            <input type="date" class="form-control @error('birthday') is-invalid @enderror" name="birthday" id="birthday" value="{{ old('birthday', $user->birthday) }}">
                            @error('birthday')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="discord_name">Discord</label>
                            <input type="text" class="form-control @error('discord_name') is-invalid @enderror" name="discord_name" id="discord_name" value="{{ old('discord_name', $user->discord_name) }}">
                            @error('discord_name')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="forum_name">Forum</label>
                            <input type="text" class="form-control @error('forum_name') is-invalid @enderror" name="forum_name" id="forum_name" value="{{ old('forum_name', $user->forum_name) }}">
                            @error('forum_name')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="hire_date">Einstellungsdatum</label>
                            {{-- Formatierung für das date-Input-Feld --}}
                            <input type="date" class="form-control @error('hire_date') is-invalid @enderror" name="hire_date" id="hire_date" value="{{ old('hire_date', optional($user->hire_date)->format('Y-m-d')) }}">
                            @error('hire_date')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
                                @foreach($statuses as $status)
                                    <option value="{{ $status }}" {{ old('status', $user->status) == $status ? 'selected' : '' }}>
                                        {{ $status }}
                                    </option>
                                @endforeach
                            </select>
                            @error('status')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                        </div>
                    </div>
                     <div class="col-md-4">
                        <div class="form-group">
                             <label for="special_functions">Sonderfunktionen</label>
                             <input type="text" class="form-control @error('special_functions') is-invalid @enderror" name="special_functions" id="special_functions" value="{{ old('special_functions', $user->special_functions) }}">
                             @error('special_functions')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                         </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group clearfix mt-4">
                            <div class="icheck-primary d-inline">
                                <input type="checkbox" id="second_faction" name="second_faction" value="1" @if(old('second_faction', $user->second_faction) == 'Ja') checked @endif>
                                <label for="second_faction">Hat eine Zweitfraktion</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Zwei-Spalten-Layout für Rechte --}}
        <div class="row">

            {{-- ============================================= --}}
            {{-- LINKE HAUPTSPALTE (Gruppen + Module)          --}}
            {{-- ============================================= --}}
            <div class="col-md-6">

                {{-- Spalte für GRUPPEN / RANG --}}
                <div class="card card-outline card-info">
                    <div class="card-header"><h3 class="card-title">Gruppen / Rang Zuweisung</h3></div>
                    <div class="card-body">
                        <p class="text-muted small">Der höchste hier ausgewählte Rang wird automatisch als Haupt-Rang des Mitarbeiters festgelegt.</p>
                        @error('roles')<div class="alert alert-danger">{{ $message }}</div>@enderror
                        @error('roles.*')<div class="alert alert-danger">{{ $message }}</div>@enderror
                        <div class="row">
                            @foreach($roles as $role)
                                <div class="col-md-4"> {{-- Hier ist col-md-4 korrekt, da es in einer inneren .row liegt --}}
                                    <div class="icheck-primary">
                                        <input type="checkbox" name="roles[]" value="{{ $role->name }}" id="role_{{ $role->id }}" @if(in_array($role->name, old('roles', $user->getRoleNames()->toArray()))) checked @endif>
                                        <label for="role_{{ $role->id }}">{{ $role->name }}</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                @can('users.manage.modules')
                {{-- Beachte: Die Klasse "col-md-4" wurde hier entfernt! --}}
                <div class="card card-outline card-success"> 
                    <div class="card-header"><h3 class="card-title"><i class="fas fa-graduation-cap"></i> Manuelle Modulzuweisung</h3></div>
                    <div class="card-body">
                        <p class="text-muted small">
                            Wähle die Module aus, die der Mitarbeiter manuell als "bestanden" zugewiesen bekommen soll.
                            Dies umgeht das Prüfungssystem. Änderungen werden protokolliert.
                            Entfernte Module werden komplett aus der Akte des Mitarbeiters gelöscht.
                        </p>
                        @error('modules.*')<div class="alert alert-danger">{{ $message }}</div>@enderror
                        <div class="row">
                            @forelse($allModules as $module)
                                <div class="col-md-4"> {{-- Hier ist col-md-4 korrekt, da es in einer inneren .row liegt --}}
                                    <div class="icheck-primary">
                                        <input type="checkbox"
                                               name="modules[]"
                                               value="{{ $module->id }}"
                                               id="module_{{ $module->id }}"
                                               @if(in_array($module->id, old('modules', $userModules ?? []))) checked @endif>
                                        <label for="module_{{ $module->id }}">
                                            {{ $module->name }} <small class="text-muted">({{ $module->category }})</small>
                                        </label>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12">
                                    <p class="text-center text-muted">Keine Trainingsmodule im System vorhanden.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
                @endcan
                {{-- === ENDE NEUE KARTE === --}}

            </div> {{-- Ende der linken Hauptspalte (col-md-6) --}}


            {{-- ============================================= --}}
            {{-- RECHTE HAUPTSPALTE (Nur Berechtigungen)      --}}
            {{-- ============================================= --}}
            <div class="col-md-6">
                
                {{-- Spalte für EINZELNE BERECHTIGUNGEN --}}
                @role('ems-director|Super-Admin')
                <div class="card card-outline card-warning">
                    <div class="card-header"><h3 class="card-title">Einzelne Berechtigungen (erweitert)</h3></div>
                    <div class="card-body">
                        <p class="text-muted small">Ermöglicht granulare Rechte, die von den zugewiesenen Gruppen abweichen. Nur in Ausnahmefällen verwenden.</p>
                        @error('permissions.*')<div class="alert alert-danger">{{ $message }}</div>@enderror

                        {{-- === START DER ÄNDERUNG (aus vorheriger Antwort) === --}}
                        <div class="row">
                            @forelse($permissions as $module => $modulePermissions)
                                <div class="col-md-4 mb-4"> {{-- Hier evtl. auf col-md-6 ändern, wenn es nur 2 Spalten sein sollen --}}
                                    <div class="card card-body p-3 border-warning h-100"> 
                                        <h6 class="text-warning text-capitalize mb-3">{{ $module }} Modul</h6>
                                        
                                        @foreach($modulePermissions as $permission)
                                            <div class="icheck-primary">
                                                <input type="checkbox" name="permissions[]" 
                                                       value="{{ $permission->name }}" id="perm_{{ $permission->id }}"
                                                       {{ in_array($permission->name, old('permissions', $userDirectPermissions)) ? 'checked' : '' }}>
                                                
                                                <label for="perm_{{ $permission->id }}" class="small">
                                                    {{ ucfirst(str_replace('-', ' ', str_replace($module . '-', '', $permission->description ?? $permission->name))) }}
                                                    <small class="text-muted d-block">({{ $permission->name }})</small>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @empty
                                <div class="col-12">
                                    <p class="text-center text-muted">Keine Berechtigungen im System vorhanden.</p>
                                </div>
                            @endforelse
                        </div>
                        {{-- === ENDE DER ÄNDERUNG === --}}
                    </div>
                </div>
                @endrole

            </div> {{-- Ende der rechten Hauptspalte (col-md-6) --}}

        </div> {{-- Ende der äußeren .row --}}

        <div class="mt-4 mb-4 text-right"> {{-- mb-4 hinzugefügt für Abstand --}}
            <a href="{{ route('admin.users.index') }}" class="btn btn-default btn-flat">Abbrechen</a>
            <button type="submit" class="btn btn-primary btn-flat">
                <i class="fas fa-save me-1"></i> Änderungen speichern
            </button>
        </div>
    </form>
@endsection

@push('scripts')
@if ($errors->any())
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const errors = @json($errors->all());
        const errorMessage = errors.join('<br>');

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: 'Validierungsfehler!',
                html: errorMessage,
                showConfirmButton: false,
                timer: 5000,
                customClass: {
                    // Stellt sicher, dass der Toast über AdminLTE-Elementen liegt
                    container: 'adminlte-modal-z-index'
                }
            });
        } else if (typeof Toastr !== 'undefined') { // Fallback auf Toastr, falls SweetAlert nicht da ist
             toastr.error(errorMessage, 'Validierungsfehler!');
        }
    });
</script>
@endif
@endpush
