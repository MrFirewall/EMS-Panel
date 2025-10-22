<div class="card card-primary card-outline mb-4">
<div class="card-header">
<h3 class="card-title"><i class="fas fa-book-open me-2"></i> Abgeschlossene Module</h3>
</div>
<div class="card-body p-0">
<table class="table table-sm mb-0 table-striped">
<thead>
<tr>
<th>Modul</th>
<th>Status</th>
<th>Abgeschlossen</th>
</tr>
</thead>
<tbody>
@forelse($user->trainingModules as $module)
@php
// Holt den Pivot-Status
$pivot = $module->pivot;
$statusColor = 'bg-secondary';
$statusText = $pivot->status; // Standard: Datenbankwert

                    if ($pivot->status === 'bestanden') {
                        $statusColor = 'bg-success';
                        $statusText = 'Bestanden';
                    } elseif ($pivot->status === 'nicht_bestanden') {
                        $statusColor = 'bg-danger';
                        $statusText = 'Nicht bestanden';
                    } elseif ($pivot->status === 'in_ausbildung') {
                        $statusColor = 'bg-info';
                        $statusText = 'In Ausbildung';
                    } elseif ($pivot->status === 'angemeldet') {
                        $statusColor = 'bg-warning';
                        $statusText = 'Angemeldet';
                    }
                @endphp
            <tr>
                <td>{{ $module->name }}</td>
                <td><span class="badge {{ $statusColor }}">{{ $statusText }}</span></td>
                <td>{{ $pivot->completed_at ? \Carbon\Carbon::parse($pivot->completed_at)->format('d.m.Y') : '-' }}</td>
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