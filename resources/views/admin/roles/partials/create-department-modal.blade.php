@php
    // Fehler-Bag für dieses Modal prüfen
    $modalErrors = $errors->createDepartment ?? new \Illuminate\Support\MessageBag;
@endphp
<div class="modal fade" id="createDepartmentModal" tabindex="-1" role="dialog" aria-labelledby="createDepartmentModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
             <div class="card card-warning card-outline mb-0">
                <form action="{{ route('admin.departments.store') }}" method="POST">
                    @csrf
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title" id="createDepartmentModalLabel">Neue Abteilung erstellen</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                         {{-- Abteilungsname --}}
                         <div class="form-group">
                            <label for="department_name">Abteilungsname</label>
                            <input type="text"
                                   class="form-control {{ $modalErrors->has('department_name') ? 'is-invalid' : '' }}"
                                   id="department_name" name="department_name"
                                   value="{{ old('department_name') }}" required>
                             @if ($modalErrors->has('department_name'))
                                <span class="invalid-feedback"><strong>{{ $modalErrors->first('department_name') }}</strong></span>
                             @endif
                        </div>

                        {{-- NEU: Leitungsrolle auswählen --}}
                        <div class="form-group">
                            <label for="create_leitung_role_name">Leitungsrolle (Optional)</label>
                            <select class="form-control select2 {{ $modalErrors->has('leitung_role_name') ? 'is-invalid' : '' }}"
                                    id="create_leitung_role_name" name="leitung_role_name" style="width: 100%;">
                                <option value="">Keine spezielle Leitungsrolle</option>
                                @foreach($allRoleNames ?? [] as $roleName)
                                    <option value="{{ $roleName }}" {{ old('leitung_role_name') == $roleName ? 'selected' : '' }}>
                                        {{ ucfirst($roleName) }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Wähle die Rolle, die als Leitungsrolle für diese Abteilung gilt.</small>
                            @if ($modalErrors->has('leitung_role_name'))
                                <span class="invalid-feedback"><strong>{{ $modalErrors->first('leitung_role_name') }}</strong></span>
                            @endif
                        </div>

                         {{-- NEU: Minimales Rang-Level auswählen --}}
                        <div class="form-group">
                            <label for="create_min_rank_level">Min. Rang-Level für Leitungszuweisung (Optional)</label>
                            <select class="form-control select2 {{ $modalErrors->has('min_rank_level_to_assign_leitung') ? 'is-invalid' : '' }}"
                                    id="create_min_rank_level" name="min_rank_level_to_assign_leitung" style="width: 100%;">
                                <option value="">Kein minimales Level</option>
                                {{-- $allRanks ist ['rank_name' => level] --}}
                                @foreach($allRanks ?? [] as $rankName => $rankLevel)
                                    <option value="{{ $rankLevel }}" {{ old('min_rank_level_to_assign_leitung') == $rankLevel ? 'selected' : '' }}>
                                        {{ $rankLevel }} - {{ ucfirst($rankName) }}
                                    </option>
                                @endforeach
                            </select>
                             <small class="text-muted">Wähle das Mindest-Level, das ein Admin haben muss, um die Leitungsrolle dieser Abteilung zuzuweisen.</small>
                            @if ($modalErrors->has('min_rank_level_to_assign_leitung'))
                                <span class="invalid-feedback"><strong>{{ $modalErrors->first('min_rank_level_to_assign_leitung') }}</strong></span>
                            @endif
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat" data-dismiss="modal">Abbrechen</button>
                        <button type="submit" class="btn btn-warning btn-flat">Abteilung erstellen</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>