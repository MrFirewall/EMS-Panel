<div class="card card-primary card-outline mb-4">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-award me-2"></i> Prüfungen</h3>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0 table-striped">
            <thead>
                <tr>
                    <th>Datum</th>
                    <th>Neuer Rang</th>
                    <th>Ausbilder</th>
                </tr>
            </thead>
            <tbody>
                @forelse($examinations as $exam)
                    <tr>
                        <td>{{ $exam->date ? \Carbon\Carbon::parse($exam->date)->format('d.m.Y') : '-' }}</td>
                        <td><strong>{{ $exam->new_rank }}</strong></td>
                        <td>{{ $exam->examiner_name }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted">Keine Prüfungseinträge.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>