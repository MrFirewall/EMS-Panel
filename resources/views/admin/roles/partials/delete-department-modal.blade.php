<div class="modal fade" id="deleteDepartmentModal_{{ $department->id }}" tabindex="-1" role="dialog" aria-labelledby="deleteDepartmentModalLabel_{{ $department->id }}" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
             <div class="card card-danger card-outline mb-0">
                <form action="{{ route('admin.departments.destroy', $department) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header bg-danger">
                        <h5 class="modal-title" id="deleteDepartmentModalLabel_{{ $department->id }}">Abteilung löschen: {{ $department->name }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                         <p>Sind Sie sicher, dass Sie die Abteilung **"{{ $department->name }}"** löschen möchten?</p>
                         @if($department->roles()->count() > 0)
                              <div class="alert alert-danger">
                                   <i class="icon fas fa-ban"></i> Achtung:
                                   Diese Abteilung kann nicht gelöscht werden, da ihr noch <strong>{{ $department->roles()->count() }} Rolle(n)</strong> zugewiesen sind. Bitte entfernen Sie zuerst die Rollen von dieser Abteilung.
                              </div>
                         @else
                              <div class="alert alert-warning">
                                   <i class="icon fas fa-exclamation-triangle"></i> Dieser Vorgang kann nicht rückgängig gemacht werden.
                              </div>
                         @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat" data-dismiss="modal">Abbrechen</button>
                        @if($department->roles()->count() == 0)
                            <button type="submit" class="btn btn-danger btn-flat">Endgültig löschen</button>
                        @else
                             <button type="button" class="btn btn-secondary btn-flat" disabled>Löschen blockiert</button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>