@extends('layouts.app')

@section('title', 'Rollen- und Berechtigungsverwaltung')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><i class="fas fa-user-shield me-2"></i> Rollen- und Berechtigungsverwaltung</h1>
                </div>
                <div class="col-sm-6 text-right">
                    {{-- ANGEPASST: Nutzt jetzt die neue Berechtigung 'roles.create' --}}
                    @can('roles.create')
                        <button type="button" class="btn btn-sm btn-success btn-flat" data-toggle="modal" data-target="#createRoleModal">
                            <i class="fas fa-plus me-1"></i> Neue Rolle erstellen
                        </button>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        
        {{-- Linke Spalte: Rollenliste (bleibt unverändert) --}}
        <div class="col-lg-4">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Alle Rollen ({{ $roles->count() ?? 0 }})</h3>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($roles as $role)
                            <a href="{{ route('admin.roles.index', ['role' => $role->id]) }}" 
                               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center 
                                      @if(isset($currentRole) && $currentRole->id === $role->id) active @endif">
                                {{ ucfirst($role->name) }}
                                <span class="badge bg-secondary">{{ $role->users_count ?? 0 }} Nutzer</span>
                            </a>
                        @empty
                            <div class="list-group-item text-center text-muted">Keine Rollen gefunden.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- Rechte Spalte: Berechtigungsdetails / Bearbeitung --}}
        <div class="col-lg-8">
            <div class="card">
                
                @if(isset($currentRole))
                    {{-- Formular zum Bearbeiten der Rolle --}}
                    <div class="card-header bg-info">
                        <h3 class="card-title">Berechtigungen für Rolle: {{ ucfirst($currentRole->name) }}</h3>
                    </div>
                    <form action="{{ route('admin.roles.update', $currentRole) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        {{-- NEU: Das <fieldset> sperrt das gesamte Formular, wenn der User keine Edit-Rechte hat --}}
                        <fieldset @cannot('roles.edit') disabled @endcannot>
                            <div class="card-body">
                                
                                {{-- Eingabefeld für Rollenname --}}
                                <div class="form-group">
                                    <label for="role_name">Rollenname (Slug)</label>
                                    <input type="text" 
                                           class="form-control @error('name') is-invalid @enderror" 
                                           id="role_name" 
                                           name="name" 
                                           value="{{ old('name', $currentRole->name) }}" 
                                           required 
                                           @if($currentRole->name === 'ems-director') disabled @endif>
                                    @if($currentRole->name === 'ems-director')
                                        <small class="text-danger">Der Name der Super-Admin-Rolle kann nicht geändert werden.</small>
                                    @endif
                                    @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>

                                {{-- Berechtigungen nach Modul auflisten --}}
                                <h5 class="border-bottom pb-2 mb-3 mt-4">Zugewiesene Berechtigungen nach Modul:</h5>
                                <div class="row">
                                    @forelse($permissions as $module => $modulePermissions)
                                        <div class="col-md-4 mb-4">
                                            <div class="card card-body p-3 border-info">
                                                <h6 class="text-info text-capitalize mb-3">{{ $module }} Modul</h6>
                                                
                                                @foreach($modulePermissions as $permission)
                                                    <div class="icheck-primary">
                                                        <input type="checkbox" name="permissions[]" 
                                                               value="{{ $permission->name }}" id="perm-{{ $permission->id }}"
                                                               {{ in_array($permission->name, $currentRolePermissions) ? 'checked' : '' }}>
                                                        <label for="perm-{{ $permission->id }}" class="small">
                                                            {{ ucfirst(str_replace('-', ' ', str_replace($module . '-', '', $permission->description))) }}
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
                            </div>
                        </fieldset> {{-- Ende des disabled-Fieldsets --}}

                        {{-- Footer mit Speichern und Löschen --}}
                        <div class="card-footer text-right">
                            {{-- ANGEPASST: Speichern-Button nur mit 'roles.edit' Berechtigung --}}
                            @can('roles.edit')
                                <button type="submit" class="btn btn-primary btn-flat">
                                    <i class="fas fa-save me-1"></i> Änderungen speichern
                                </button>
                            @endcan
                            
                            {{-- ANGEPASST: Löschen-Button nur mit 'roles.delete' Berechtigung --}}
                            @can('roles.delete')
                                @if($currentRole->name !== 'ems-director')
                                    <button type="button" class="btn btn-danger btn-flat ml-2" data-toggle="modal" data-target="#deleteRoleModal">
                                        <i class="fas fa-trash-alt me-1"></i> Rolle löschen
                                    </button>
                                @endif
                            @endcan
                        </div>
                    </form>
                    
                    @include('admin.roles.partials.delete-modal')

                @else
                    {{-- Platzhalter, wenn keine Rolle ausgewählt ist (bleibt unverändert) --}}
                    <div class="card-body text-center py-5">
                        <p class="lead text-muted">Bitte wähle links eine Rolle aus, um deren Berechtigungen zu bearbeiten.</p>
                        <i class="fas fa-arrow-left fa-4x text-primary"></i>
                    </div>
                @endif
                
            </div>
        </div>
    </div>
    
    @include('admin.roles.partials.create-modal')

@endsection