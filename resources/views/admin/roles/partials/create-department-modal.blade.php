@php
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
                         {{-- OPTIONAL: Felder für Leitung etc. hinzufügen --}}
                        {{-- 
                        <div class="form-group"> ... leitung_role_name ... </div>
                        <div class="form-group"> ... min_rank_level_to_assign_leitung ... </div>
                         --}}
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