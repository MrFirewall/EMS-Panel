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

    {{-- WICHTIG: Der ursprüngliche Alert-Block wird entfernt, da er durch JS ersetzt wird. --}}

    <form method="POST" action="{{ route('admin.users.update', $user) }}">
        @csrf
        @method('PUT')

        {{-- Stammdaten-Karte --}}
        <div class="card card-outline card-primary mb-4">
            <div class="card-header"><h3 class="card-title">Stammdaten</h3></div>
            <div class="card-body">
                {{-- BS5 g-3 row ersetzt durch BS4 row --}}
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="name">Mitarbeiter Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" id="name" value="{{ old('name', $user->name) }}">
                            @error('name')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="personal_number">Personalnummer</label>
                            <select name="personal_number" id="personal_number" class="form-control @error('personal_number') is-invalid @enderror" required>
                                <option value="">Bitte wählen...</option>
                                @if($user->personal_number)
                                    <option value="{{ $user->personal_number }}" selected>{{ $user->personal_number }} (Aktuell)</option>
                                @endif
                                @foreach($availablePersonalNumbers as $number)
                                    <option value="{{ $number }}" @if(old('personal_number') == $number) selected @endif>{{ $number }}</option>
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
                            <input type="date" class="form-control @error('hire_date') is-invalid @enderror" name="hire_date" id="hire_date" value="{{ old('hire_date', $user->hire_date) }}">
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
                        <div class="form-group clearfix mt-4">
                            {{-- form-check form-switch ersetzt durch icheck-primary --}}
                            <div class="icheck-primary d-inline">
                                <input type="checkbox" id="second_faction" name="second_faction" @if(old('second_faction', $user->second_faction) == 'Ja') checked @endif>
                                <label for="second_faction">Hat eine Zweitfraktion</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Zwei-Spalten-Layout für Rechte --}}
        <div class="row">
            {{-- Spalte für GRUPPEN / RANG --}}
            <div class="col-md-12">
                <div class="card card-outline card-info">
                    <div class="card-header"><h3 class="card-title">Gruppen / Rang Zuweisung</h3></div>
                    <div class="card-body">
                        <p class="text-muted small">Der höchste hier ausgewählte Rang wird automatisch als Haupt-Rang des Mitarbeiters festgelegt.</p>
                        <div class="row">
                            @foreach($roles as $role)
                                <div class="col-md-6">
                                    <div class="icheck-primary">
                                        <input type="checkbox" name="roles[]" value="{{ $role->name }}" id="role_{{ $role->id }}" @if(in_array($role->name, old('roles', $user->getRoleNames()->toArray()))) checked @endif>
                                        <label for="role_{{ $role->id }}">{{ $role->name }}</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            @role('ems-director')
            {{-- Spalte für EINZELNE BERECHTIGUNGEN --}}
            <div class="col-md-12">
                <div class="card card-outline card-warning">
                    <div class="card-header"><h3 class="card-title">Einzelne Berechtigungen (erweitert)</h3></div>
                    <div class="card-body">
                        <p class="text-muted small">Ermöglicht granulare Rechte, die von den zugewiesenen Gruppen abweichen. Nur in Ausnahmefällen verwenden.</p>
                        <div class="row">
                            @foreach($permissions as $permission)
                                <div class="col-md-6">
                                    <div class="icheck-primary">
                                        <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" id="perm_{{ $permission->id }}" @if(in_array($permission->name, old('permissions', $user->getPermissionNames()->toArray()))) checked @endif>
                                        <label for="perm_{{ $permission->id }}">{{ $permission->name }}</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            @endrole
        </div>
        
        <div class="mt-4 text-right">
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
        
        // Formatiert alle Fehler in einem String (für Toast)
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
                    container: 'adminlte-modal-z-index'
                }
            });
        }
    });
</script>
@endif
@endpush
