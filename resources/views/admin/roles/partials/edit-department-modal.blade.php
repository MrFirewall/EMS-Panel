@php
    // Eindeutigen Fehler-Bag für dieses spezifische Modal prüfen
    $modalErrors = $errors->{'editDepartment_' . $department->id} ?? new \Illuminate\Support\MessageBag;
@endphp
<div class="modal fade" id="editDepartmentModal_{{ $department->id }}" tabindex="-1" role="dialog" aria-labelledby="editDepartmentModalLabel_{{ $department->id }}" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
             <div class="card card-primary card-outline mb-0">
                <form action="{{ route('admin.departments.update', $department) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header bg-primary">
                        <h5 class="modal-title" id="editDepartmentModalLabel_{{ $department->id }}">Abteilung bearbeiten: {{ $department->name }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                         {{-- Abteilungsname --}}
                         <div class="form-group">
                            <label for="edit_department_name_{{ $department->id }}">Neuer Abteilungsname</label>
                            <input type="text"
                                   class="form-control {{ $modalErrors->has('edit_department_name') ? 'is-invalid' : '' }}"
                                   id="edit_department_name_{{ $department->id }}" name="edit_department_name"
                                   value="{{ old('edit_department_name', $department->name) }}" required>
                             @if ($modalErrors->has('edit_department_name'))
                                <span class="invalid-feedback"><strong>{{ $modalErrors->first('edit_department_name') }}</strong></span>
                             @endif
                        </div>

                         {{-- NEU: Leitungsrolle auswählen --}}
                        <div class="form-group">
                            <label for="edit_leitung_role_name_{{ $department->id }}">Leitungsrolle (Optional)</label>
                            <select class="form-control select2 {{ $modalErrors->has('edit_leitung_role_name') ? 'is-invalid' : '' }}"
                                    id="edit_leitung_role_name_{{ $department->id }}" name="edit_leitung_role_name" style="width: 100%;">
                                <option value="">Keine spezielle Leitungsrolle</option>
                                @foreach($allRoleNames ?? [] as $roleName)
                                    <option value="{{ $roleName }}"
                                            {{ old('edit_leitung_role_name', $department->leitung_role_name) == $roleName ? 'selected' : '' }}>
                                        {{ ucfirst($roleName) }}
                                    </option>
                                @endforeach
                            </select>
                             <small class="text-muted">Wähle die Rolle, die als Leitungsrolle für diese Abteilung gilt.</small>
                            @if ($modalErrors->has('edit_leitung_role_name'))
                                <span class="invalid-feedback"><strong>{{ $modalErrors->first('edit_leitung_role_name') }}</strong></span>
                            @endif
                        </div>

                         {{-- NEU: Minimales Rang-Level auswählen --}}
                        <div class="form-group">
                            <label for="edit_min_rank_level_{{ $department->id }}">Min. Rang-Level für Leitungszuweisung (Optional)</label>
                             <select class="form-control select2 {{ $modalErrors->has('edit_min_rank_level_to_assign_leitung') ? 'is-invalid' : '' }}"
                                    id="edit_min_rank_level_{{ $department->id }}" name="edit_min_rank_level_to_assign_leitung" style="width: 100%;">
                                <option value="">Kein minimales Level</option>
                                @foreach($allRanks ?? [] as $rankName => $rankLevel)
                                    <option value="{{ $rankLevel }}"
                                            {{ old('edit_min_rank_level_to_assign_leitung', $department->min_rank_level_to_assign_leitung) == $rankLevel ? 'selected' : '' }}>
                                        {{ $rankLevel }} - {{ ucfirst($rankName) }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Wähle das Mindest-Level, das ein Admin haben muss, um die Leitungsrolle dieser Abteilung zuzuweisen.</small>
                            @if ($modalErrors->has('edit_min_rank_level_to_assign_leitung'))
                                <span class="invalid-feedback"><strong>{{ $modalErrors->first('edit_min_rank_level_to_assign_leitung') }}</strong></span>
                            @endif
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat" data-dismiss="modal">Abbrechen</button>
                        <button type="submit" class="btn btn-primary btn-flat">Änderungen speichern</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>