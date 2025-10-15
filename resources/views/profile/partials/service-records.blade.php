<div class="card card-outline card-secondary h-100">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-file-signature me-2"></i> Akteneinträge</h3>
    </div>

    {{-- Das Formular für neue Einträge bleibt unverändert --}}
    <div class="card-body border-bottom">
        <form action="{{ route('admin.users.records.store', $user) }}" method="POST">
            @csrf
            <h6 class="text-muted small mb-3">Neuen Vermerk hinzufügen:</h6>
            <div class="form-group">
                <label for="type" class="small">Art des Eintrags</label>
                <select class="form-control form-control-sm" name="type" id="type" required>
                    <option value="Positiver Vermerk">Positiver Vermerk</option>
                    <option value="Fortbildung">Fortbildung</option>
                    <option value="Negativer Vermerk">Negativer Vermerk</option>
                    <option value="Beförderung">Beförderung</option>
                    <option value="Degradierung">Degradierung</option>
                </select>
            </div>
            <div class="form-group">
                <label for="content" class="small">Inhalt</label>
                <textarea name="content" id="content" class="form-control form-control-sm" rows="3" required></textarea>
            </div>
            <button type="submit" class="btn btn-sm btn-info btn-block btn-flat mt-2">
                <i class="fas fa-save me-1"></i> Vermerk speichern
            </button>
        </form>
    </div>

    {{-- ====================================================================== --}}
    {{-- NEU: Der Scroll-Container umschließt die Liste der Akteneinträge --}}
    {{-- ====================================================================== --}}
    <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
        <ul class="list-group list-group-flush">
            @forelse($serviceRecords as $record)
                <li class="list-group-item">
                    <span class="text-xs float-right text-muted">{{ $record->created_at->format('d.m.Y') }}</span>
                    <b>{{ $record->type }}</b>
                    <p class="text-sm mb-0">{{ $record->content }}</p>
                    <small class="text-muted">Von: {{ $record->author->name }}</small>
                </li>
            @empty
                <li class="list-group-item text-center text-muted small">Keine Einträge vorhanden.</li>
            @endforelse
        </ul>
    </div>

</div>