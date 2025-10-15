@extends('layouts.app')

@section('title', 'Neuen Mitarbeiter anlegen')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <h1 class="m-0">Neuen Mitarbeiter anlegen</h1>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.users.store') }}">
        @csrf

        {{-- Stammdaten-Karte --}}
        <div class="card card-outline card-primary mb-4">
            <div class="card-header"><h3 class="card-title">Stammdaten des Mitarbeiters</h3></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="name">Mitarbeiter Name</label>
                            <input type="text" class="form-control" name="name" id="name" value="{{ old('name') }}" placeholder="Max Mustermann" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="cfx_id">CFX.re ID</label>
                            <input type="text" class="form-control" name="cfx_id" id="cfx_id" value="{{ old('cfx_id') }}" placeholder="1234567" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select name="status" id="status" class="form-control" required>
                                {{-- Standard-Auswahl, falls nichts anderes zutrifft --}}
                                <option value="" disabled>Bitte auswählen</option>
                                
                                @foreach($statuses as $status)
                                    <option value="{{ $status }}" {{ old('status', 'Bewerbungsphase') == $status ? 'selected' : '' }}>
                                        {{ $status }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="employee_id">Mitarbeiter ID</label>
                            <input type="text" class="form-control" name="employee_id" id="employee_id" value="{{ old('employee_id') }}" disabled placeholder="Wird automatisch generiert">
                            <small class="text-muted">Die ID wird automatisch nach dem Anlegen generiert.</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="email">E-Mail</label>
                            <input type="email" class="form-control" name="email" id="email" value="{{ old('email') }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="birthday">Geburtstag</label>
                            <input type="date" class="form-control" name="birthday" id="birthday" value="{{ old('birthday') }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="discord_name">Discord</label>
                            <input type="text" class="form-control" name="discord_name" id="discord_name" value="{{ old('discord_name') }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="forum_name">Forum</label>
                            <input type="text" class="form-control" name="forum_name" id="forum_name" value="{{ old('forum_name') }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="hire_date">Einstellungsdatum</label>
                            <input type="date" class="form-control" name="hire_date" id="hire_date" value="{{ old('hire_date') ?? \Carbon\Carbon::now()->format('Y-m-d') }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group clearfix mt-4">
                            <div class="icheck-primary d-inline">
                                <input type="checkbox" id="second_faction" name="second_faction" {{ old('second_faction') ? 'checked' : '' }}>
                                <label for="second_faction">Hat eine Zweitfraktion</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title">Gruppen / Rang Zuweisung</h3>
            </div>
            <div class="card-body">
                <p class="text-muted small">Wähle die Gruppen aus, die der Benutzer haben soll. Der höchste Rang wird automatisch als Haupt-Rang festgelegt.</p>
                <div class="row">
                    @foreach($roles as $role)
                        <div class="col-md-4">
                            <div class="icheck-primary">
                                <input type="checkbox" name="roles[]" value="{{ $role->name }}" id="role_{{ $role->id }}" {{ in_array($role->name, old('roles', [])) ? 'checked' : '' }}>
                                <label for="role_{{ $role->id }}">{{ $role->name }}</label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="mt-4 text-right">
            <a href="{{ route('admin.users.index') }}" class="btn btn-default btn-flat">Abbrechen</a>
            <button type="submit" class="btn btn-primary btn-flat">
                <i class="fas fa-user-plus me-1"></i> Mitarbeiter anlegen
            </button>
        </div>
    </form>
@endsection
