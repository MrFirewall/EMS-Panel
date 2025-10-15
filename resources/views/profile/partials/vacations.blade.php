<div class="card card-warning card-outline">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-plane me-2"></i> Urlaub / Abwesenheiten</h3>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0 small table-striped">
            <thead>
                <tr>
                    <th>Von</th>
                    <th>Bis</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($vacations as $vacation)
                    <tr>
                        <td>{{ $vacation->start_date ? \Carbon\Carbon::parse($vacation->start_date)->format('d.m.Y') : '-' }}</td>
                        <td>{{ $vacation->end_date ? \Carbon\Carbon::parse($vacation->end_date)->format('d.m.Y') : '-' }}</td>
                        <td>
                            @if($vacation->status === 'approved')
                                <span class="badge bg-success">Genehmigt</span>
                            @elseif($vacation->status === 'pending')
                                <span class="badge bg-warning">Ausstehend</span>
                            @else
                                <span class="badge bg-danger">Abgelehnt</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted">Keine Urlaubseinträge.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>