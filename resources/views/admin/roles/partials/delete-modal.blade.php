@if(isset($currentRole))
    <div class="modal fade" id="deleteRoleModal" tabindex="-1" role="dialog" aria-labelledby="deleteRoleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                
                {{-- Formular muss die ID der aktuellen Rolle verwenden --}}
                <form action="{{ route('admin.roles.destroy', $currentRole) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    
                    <div class="modal-header bg-danger">
                        <h5 class="modal-title" id="deleteRoleModalLabel">Rolle löschen: {{ ucfirst($currentRole->name) }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Schließen">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    
                    <div class="modal-body">
                        <p>Sind Sie sicher, dass Sie die Rolle **"{{ ucfirst($currentRole->name) }}"** löschen möchten?</p>
                        
                        <div class="alert alert-warning">
                            <i class="icon fas fa-exclamation-triangle"></i> Achtung:
                            Alle Mitarbeiter, die diesen Rang zugewiesen haben, verlieren ihn. 
                            Dieser Vorgang kann nicht rückgängig gemacht werden.
                        </div>
                        
                        @if($currentRole->users_count > 0)
                            <p class="text-danger">
                                **HINWEIS:** Aktuell sind **{{ $currentRole->users_count }}** Mitarbeiter dieser Rolle zugewiesen. Die Rolle kann erst gelöscht werden, wenn alle Mitarbeiter entfernt wurden.
                            </p>
                            <input type="hidden" name="force_delete" value="0">
                        @endif
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat" data-dismiss="modal">Abbrechen</button>
                        
                        @if($currentRole->users_count == 0)
                            <button type="submit" class="btn btn-danger btn-flat">
                                <i class="fas fa-trash-alt me-1"></i> Rolle endgültig löschen
                            </button>
                        @else
                            <button type="button" class="btn btn-secondary btn-flat" disabled>
                                Löschen blockiert (Mitarbeiter zugewiesen)
                            </button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
