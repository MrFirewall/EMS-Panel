<div class="card card-primary card-outline mb-4">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-book-open me-2"></i> Abgeschlossene Module</h3>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0 table-striped">
            <thead>
                <tr>
                    <th>Datum</th>
                    <th>Modul</th>
                    <th>Ausbilder</th>
                </tr>
            </thead>
            <tbody>
                @forelse($user->trainingModules as $module)
                <tr>
                    
                    <td>{{ $pivot->created_at ? \Carbon\Carbon::parse($pivot->created_at)->format('d.m.Y') : '-' }}</td>
                    <td>{{ $module->name }}</td>
                    <td>{{ $module->assigned_by_user_id : 'Angemeldet' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="text-center text-muted">Keine Moduleinträge.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>