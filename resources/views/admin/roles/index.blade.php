@extends('layouts.app')

@section('title', 'Rollen- und Berechtigungsverwaltung')

{{-- NEU: Eigene Styles für den Drag-and-Drop-Modus --}}
@push('styles')
<style>
    /* Versteckt die normalen Links im Bearbeiten-Modus */
    .rank-list.is-editing .rank-link {
        display: none;
    }
    /* Zeigt die Bearbeitungs-Items (mit Handle) nur im Bearbeiten-Modus an */
    .rank-edit-item {
        display: none; /* Standardmäßig versteckt */
        align-items: center;
        width: 100%;
        padding: 0.75rem 1.25rem; /* Gleiches Padding wie list-group-item */
    }
    .rank-list.is-editing .rank-edit-item {
        display: flex; 
    }
    /* Das list-group-item selbst wird zum Container */
    .rank-list.is-editing .list-group-item {
        cursor: grab;
        padding: 0; /* Padding wird vom Kind-Element übernommen */
    }
    /* Styling für den Drag-Handle */
    .rank-handle {
        cursor: grab;
        margin-right: 15px;
        color: #999;
    }
    .rank-edit-item span:first-of-type {
        flex-grow: 1; /* Sorgt dafür, dass der Name den Platz einnimmt */
    }
    /* Styling für den "Bearbeiten" Button, wenn aktiv */
    #toggle-rank-edit.active {
        color: #007bff; /* Blau, um Aktivität zu signalisieren */
    }

    /* Style für die Department Management Liste */
    .department-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 1rem;
        border-bottom: 1px solid #eee;
    }
    .department-item:last-child {
        border-bottom: none;
    }
    .department-actions .btn {
        margin-left: 5px;
    }
</style>
@endpush

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><i class="fas fa-user-shield me-2"></i> Rollen- und Berechtigungsverwaltung</h1>
                </div>
                <div class="col-sm-6 text-right">
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
        
        {{-- ======================================================= --}}
        {{-- Linke Spalte: Rollenliste (STARK ANGEPASST)             --}}
        {{-- ======================================================= --}}
        <div class="col-lg-4">

            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Ränge (Hierarchie)</h3>
                    <div class="card-tools">
                        @can('roles.edit')
                        <button type="button" class="btn btn-xs btn-tool" id="toggle-rank-edit" title="Hierarchie bearbeiten">
                            <i class="fas fa-edit"></i>
                        </button>
                        @endcan
                    </div>
                </div>
                <div class="card-body p-0">
                    {{-- Diese <ul> wird per JS sortierbar gemacht --}}
                    <ul class="list-group list-group-flush rank-list" id="rank-sort-list">
                        @forelse($categorizedRoles['Ranks'] as $role)
                            {{-- WICHTIG: data-rank-id enthält die ID aus der 'ranks'-Tabelle --}}
                            <li class="list-group-item" data-id="{{ $role->rank_id }}">
                                
                                {{-- Ansicht 1: Normaler Link (Standard) --}}
                                <a href="{{ route('admin.roles.index', ['role' => $role->id]) }}"
                                   class="rank-link d-flex justify-content-between align-items-center @if(isset($currentRole) && $currentRole->id === $role->id) active @endif">
                                    <span>{{ ucfirst($role->name) }}</span>
                                    <span class="badge bg-secondary">{{ $role->users_count ?? 0 }} Nutzer</span>
                                </a>
                                
                                {{-- Ansicht 2: Bearbeitungs-Item (Versteckt) --}}
                                <div class="rank-edit-item">
                                    <i class="fas fa-grip-vertical rank-handle"></i>
                                    <span>{{ ucfirst($role->name) }}</span>
                                    <span class="badge bg-secondary">{{ $role->users_count ?? 0 }} Nutzer</span>
                                </div>
                            </li>
                        @empty
                            <div class="list-group-item text-center text-muted">Keine Ränge in der 'ranks'-Tabelle definiert.</div>
                        @endforelse
                    </ul>
                    {{-- Steuer-Buttons für den Bearbeiten-Modus --}}
                    <div class="p-2" id="rank-edit-controls" style="display: none;">
                        <button id="save-rank-order" class="btn btn-success btn-sm btn-flat">Speichern</button>
                        <button id="cancel-rank-order" class="btn btn-default btn-sm btn-flat">Abbrechen</button>
                    </div>
                </div>
            </div>

            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">Abteilungen</h3>
                </div>
                <div class="card-body p-0">
                    @forelse($categorizedRoles['Departments'] as $deptName => $deptRoles)
                        @if(!empty($deptRoles))
                            {{-- Abteilungs-Titel --}}
                            <div class="list-group-item list-group-item-secondary bg-secondary">
                                {{ $deptName }}
                            </div>
                            {{-- Zugehörige Rollen --}}
                            <div class="list-group list-group-flush">
                                @foreach($deptRoles as $role)
                                    <a href="{{ route('admin.roles.index', ['role' => $role->id]) }}" 
                                       class="list-group-item list-group-item-action d-flex justify-content-between align-items-center
                                              @if(isset($currentRole) && $currentRole->id === $role->id) active @endif">
                                        {{ ucfirst($role->name) }}
                                        <span class="badge bg-secondary">{{ $role->users_count ?? 0 }} Nutzer</span>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    @empty
                         <div class="list-group-item text-center text-muted">Keine Abteilungen gefunden.</div>
                    @endforelse
                </div>
            </div>

            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Andere Rollen</h3>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($categorizedRoles['Other'] as $role)
                            <a href="{{ route('admin.roles.index', ['role' => $role->id]) }}" 
                               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center 
                                      @if(isset($currentRole) && $currentRole->id === $role->id) active @endif">
                                {{ ucfirst($role->name) }}
                                <span class="badge bg-secondary">{{ $role->users_count ?? 0 }} Nutzer</span>
                            </a>
                        @empty
                            <div class="list-group-item text-center text-muted">Keine weiteren Rollen gefunden.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- ============================================= --}}
            {{-- NEU: Department Management Card             --}}
            {{-- ============================================= --}}
             <div class="card card-outline card-warning">
                 <div class="card-header">
                     <h3 class="card-title">Abteilungen Verwalten</h3>
                     <div class="card-tools">
                         @can('roles.create')
                         <button type="button" class="btn btn-xs btn-success btn-flat" data-toggle="modal" data-target="#createDepartmentModal" title="Neue Abteilung erstellen">
                             <i class="fas fa-plus"></i> Neu
                         </button>
                         @endcan
                     </div>
                 </div>
                 <div class="card-body p-0">
                     @forelse($allDepartments as $department)
                         <div class="department-item">
                             <span>{{ $department->name }}</span>
                             <div class="department-actions">
                                 @can('roles.edit')
                                 <button type="button" class="btn btn-xs btn-primary btn-flat" data-toggle="modal" data-target="#editDepartmentModal_{{ $department->id }}" title="Umbenennen">
                                     <i class="fas fa-edit"></i>
                                 </button>
                                 @endcan
                                 @can('roles.delete')
                                 <button type="button" class="btn btn-xs btn-danger btn-flat" data-toggle="modal" data-target="#deleteDepartmentModal_{{ $department->id }}" title="Löschen">
                                     <i class="fas fa-trash"></i>
                                 </button>
                                 @endcan
                             </div>
                         </div>
                     @empty
                          <div class="p-3 text-center text-muted">Keine Abteilungen vorhanden.</div>
                     @endforelse
                 </div>
             </div>

        </div>

        {{-- ======================================================= --}}
        {{-- Rechte Spalte: Berechtigungsdetails (KEINE ÄNDERUNG)     --}}
        {{-- ======================================================= --}}
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
                                           @if($currentRole->name === 'chief') disabled @endif>
                                    @if($currentRole->name === 'chief')
                                        <small class="text-danger">Der Name der Super-Admin-Rolle kann nicht geändert werden.</small>
                                    @endif
                                    @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                                {{-- ============================================= --}}
                                {{-- NEU: Rollentyp Auswahl                       --}}
                                {{-- ============================================= --}}
                                <div class="form-group">
                                    <label>Rollentyp</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="role_type" id="type_rank" value="rank" 
                                               {{ old('role_type', $currentRoleType) == 'rank' ? 'checked' : '' }} 
                                               @if($currentRole->name === 'chief') disabled @endif> {{-- Chief kann kein anderer Typ werden --}}
                                        <label class="form-check-label" for="type_rank">Rang (wird in Hierarchie einsortiert)</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="role_type" id="type_department" value="department" 
                                               {{ old('role_type', $currentRoleType) == 'department' ? 'checked' : '' }}
                                               @if($currentRole->name === 'chief') disabled @endif>
                                        <label class="form-check-label" for="type_department">Abteilungsrolle</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="role_type" id="type_other" value="other" 
                                               {{ old('role_type', $currentRoleType) == 'other' ? 'checked' : '' }}
                                               @if($currentRole->name === 'chief') disabled @endif>
                                        <label class="form-check-label" for="type_other">Andere Rolle</label>
                                    </div>
                                    @error('role_type', 'updateRole')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>
                                
                                {{-- ============================================= --}}
                                {{-- NEU: Department Auswahl (Konditional)        --}}
                                {{-- ============================================= --}}
                                <div class="form-group" id="edit_department_select_group" style="{{ old('role_type', $currentRoleType) == 'department' ? '' : 'display: none;' }}">
                                     <label for="edit_department_id">Zugehörige Abteilung</label>
                                     <select name="department_id" id="edit_department_id" class="form-control @error('department_id', 'updateRole') is-invalid @enderror">
                                         <option value="">Bitte Abteilung wählen...</option>
                                         @foreach($allDepartments as $department)
                                             <option value="{{ $department->id }}" 
                                                     {{ old('department_id', $currentDepartmentId) == $department->id ? 'selected' : '' }}>
                                                 {{ $department->name }}
                                             </option>
                                         @endforeach
                                     </select>
                                     @error('department_id', 'updateRole')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                                                            {{-- Nimmt den Teil nach dem Punkt --}}
                                                            {{ ucfirst(str_replace('-', ' ', explode('.', $permission->name)[1] ?? $permission->name)) }}
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
                        </fieldset>

                        {{-- Footer mit Speichern und Löschen --}}
                        <div class="card-footer text-right">
                            @can('roles.edit')
                                <button type="submit" class="btn btn-primary btn-flat">
                                    <i class="fas fa-save me-1"></i> Änderungen speichern
                                </button>
                            @endcan
                            
                            @can('roles.delete')
                                @if($currentRole->name !== 'chief')
                                    <button type="button" class="btn btn-danger btn-flat ml-2" data-toggle="modal" data-target="#deleteRoleModal">
                                        <i class="fas fa-trash-alt me-1"></i> Rolle löschen
                                    </button>
                                @endif
                            @endcan
                        </div>
                    </form>
                    
                    @include('admin.roles.partials.delete-modal')

                @else
                    {{-- Platzhalter, wenn keine Rolle ausgewählt ist --}}
                    <div class="card-body text-center py-5">
                        <p class="lead text-muted">Bitte wähle links eine Rolle aus, um deren Berechtigungen zu bearbeiten.</p>
                        <i class="fas fa-arrow-left fa-4x text-primary"></i>
                    </div>
                @endif
                
            </div>
        </div>
    </div>
    
@include('admin.roles.partials.create-modal') {{-- Wichtig: Dieser Include muss da sein --}}
    @include('admin.roles.partials.create-department-modal')
    @foreach($allDepartments as $department)
        @include('admin.roles.partials.edit-department-modal', ['department' => $department])
        @include('admin.roles.partials.delete-department-modal', ['department' => $department])
    @endforeach

@endsection

{{-- ======================================================= --}}
{{-- KORRIGIERTER JAVASCRIPT BLOCK                          --}}
{{-- ======================================================= --}}
@push('scripts')
{{-- Bibliotheken (Reihenfolge ist wichtig) --}}
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

<script>
// Alles innerhalb von $(function() { ... }) ausführen,
// um sicherzustellen, dass das DOM bereit ist.
$(function () {

    // --- SortableJS Logik für Ränge ---
    let sortable = null;
    const rankList = document.getElementById('rank-sort-list');
    const editButton = $('#toggle-rank-edit');
    const saveButton = $('#save-rank-order');
    const cancelButton = $('#cancel-rank-order');
    const editControls = $('#rank-edit-controls');
    const listContainer = $('.rank-list');

    function toggleEditMode(isEditing) {
        // ... (toggleEditMode Funktion bleibt unverändert) ...
         if (isEditing) {
            listContainer.addClass('is-editing');
            editControls.show();
            editButton.addClass('active');
            $('.rank-link').hide();
            $('.rank-edit-item').css('display', 'flex'); 
            if (!sortable && rankList) { // Prüfen ob rankList existiert
                sortable = new Sortable(rankList, {
                    handle: '.rank-handle', 
                    animation: 150,
                });
            }
        } else {
            listContainer.removeClass('is-editing');
            editControls.hide();
            editButton.removeClass('active');
            $('.rank-link').show();
            $('.rank-edit-item').hide();
            if (sortable) {
                sortable.destroy();
                sortable = null;
            }
        }
    }

    if (editButton.length) { // Nur ausführen, wenn der Button existiert
        editButton.on('click', function() {
            toggleEditMode(!listContainer.hasClass('is-editing'));
        });
    }

    if (cancelButton.length) {
        cancelButton.on('click', function() {
            window.location.reload(); 
        });
    }

    if (saveButton.length) {
        saveButton.on('click', function() {
            if (!sortable) return;
            const order = sortable.toArray(); 
            saveButton.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Speichern...');

            fetch('{{ route("admin.roles.ranks.reorder") }}', { /* ... (Fetch bleibt unverändert) ... */ })
            .then(response => { /* ... */ })
            .then(data => { /* ... */ })
            .catch(error => { /* ... */ })
            .finally(() => { saveButton.prop('disabled', false).html('Speichern'); });
        });
    }

    // --- Logik für Typ/Department Auswahl in Modals ---

    // Create Role Modal
    const createRoleTypeRadios = $('#createRoleModal input[type=radio][name="role_type"]'); // Selektor präzisiert
    const createDepartmentSelectGroup = $('#create_department_select_group');

    if (createRoleTypeRadios.length) { // Nur ausführen, wenn Elemente existieren
        createRoleTypeRadios.on('change', function() {
            if (this.value === 'department') {
                createDepartmentSelectGroup.slideDown();
            } else {
                createDepartmentSelectGroup.slideUp();
            }
        });
        // Initial check
        if (createRoleTypeRadios.filter(':checked').val() === 'department') {
             createDepartmentSelectGroup.show();
        } else {
             createDepartmentSelectGroup.hide();
        }
    }

    // Edit Role Form (im Hauptteil der Seite)
    const editRoleTypeRadios = $('#editRoleForm input[type=radio][name="role_type"]');
    const editDepartmentSelectGroup = $('#edit_department_select_group');

    if (editRoleTypeRadios.length) { // Nur ausführen, wenn Elemente existieren
        editRoleTypeRadios.on('change', function() {
            if (this.value === 'department') {
                editDepartmentSelectGroup.slideDown();
            } else {
                editDepartmentSelectGroup.slideUp();
            }
        });
        // Initial state wird durch Inline-Style im HTML gesetzt
    }

    // --- Handling Modal Opening on Validation Error ---
    // Diese Logik sollte jetzt zuverlässiger sein, da sie im ready-Handler steht.

    // 1. Prüfen, ob ein bestimmtes Modal geöffnet werden soll (Signal aus dem Controller)
    const modalToOpen = @json(session('open_modal')); // Hole den Wert sicher
    if (modalToOpen) {
        const modalElement = $('#' + modalToOpen);
        if (modalElement.length) { // Prüfen, ob das Modal existiert
             console.log('Versuche Modal zu öffnen:', modalToOpen); // Debugging
             modalElement.modal('show');
        } else {
             console.error('Modal mit ID ' + modalToOpen + ' nicht gefunden!'); // Debugging
        }
    }

    // 2. Prüfen auf Fehler-Bags und entsprechende Modals öffnen
    @if($errors->any()) // Nur ausführen, wenn überhaupt Fehler da sind
        @if($errors->hasBag('createRole'))
            if ($('#createRoleModal').length) { $('#createRoleModal').modal('show'); }
        @endif
        @if($errors->hasBag('createDepartment'))
            if ($('#createDepartmentModal').length) { $('#createDepartmentModal').modal('show'); }
        @endif

        @foreach($allDepartments as $department)
            @if($errors->hasBag('editDepartment_' . $department->id))
                if ($('#editDepartmentModal_{{ $department->id }}').length) { $('#editDepartmentModal_{{ $department->id }}').modal('show'); }
            @endif
             @if($errors->hasBag('deleteDepartment_' . $department->id))
                 if ($('#deleteDepartmentModal_{{ $department->id }}').length) { $('#deleteDepartmentModal_{{ $department->id }}').modal('show'); }
             @endif
        @endforeach
    @endif

});
</script>
@endpush