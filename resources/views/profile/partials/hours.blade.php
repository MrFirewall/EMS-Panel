{{-- Hilfsfunktion zur Umrechnung von Sekunden in HH:MM Format --}}
@php
function formatSeconds($seconds) {
    if ($seconds < 1) return '00:00';
    $h = floor($seconds / 3600);
    $m = floor(($seconds % 3600) / 60);
    return sprintf('%02d:%02d', $h, $m);
}
// Mapping von Rank-Slugs zu lesbaren Namen (kann erweitert werden)
$rankNames = [
    'chief'         => 'Chief',
    'deputy chief'  => 'Deputy Chief',
    'doctor'        => 'Doctor',
    'captain'       => 'Captain',
    'lieutenant'    => 'Lieutenant',
    'supervisor'    => 'Supervisor',
    's-emt'         => 'S-EMT (Senior EMT)',
    'paramedic'     => 'Paramedic',
    'a-emt'         => 'A-EMT (Advanced EMT)',
    'emt'           => 'EMT (Emergency Medical Technician)',
    'trainee'       => 'Trainee',
];
@endphp

{{-- Box: Aktive Stunden --}}
<div class="row">
    <div class="col-md-4">
        <div class="card card-info mb-3">
            <div class="card-header">
                <h3 class="card-title">Wochenstunden</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm m-0">
                        <thead>
                            <tr>
                                <th>KW</th>
                                <th>Stunden</th>
                                <th>Leitstelle</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($weeklyHours as $kw => $hours)
                                <tr>
                                    <td>{{ $kw }}</td>
                                    <td>{{ formatSeconds($hours['normal_seconds']) }} h</td>
                                    <td>{{ formatSeconds($hours['leitstelle_seconds']) }} h</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">Keine wöchentlichen Daten vorhanden.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-primary mb-3">
            <div class="card-header">
                <h3 class="card-title">Stunden (Aktiver Zeitraum)</h3>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Dienstzeit gesamt
                        <span>{{ formatSeconds($hourData['active_total_seconds']) }} h</span>
                    </li>
                    {{-- Hier könnten später Leitstellenstunden etc. hinzukommen --}}
                </ul>
            </div>
        </div>
    </div>
    {{-- Box: Stundenarchiv nach Rang --}}

    <div class="col-md-4">
        <div class="card card-secondary">
            <div class="card-header">
                <h3 class="card-title">Stundenarchiv</h3>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse ($hourData['archive_by_rank'] as $rank => $seconds)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            {{-- Zeigt den lesbaren Namen oder den Slug an --}}
                            {{ $rankNames[$rank] ?? ucfirst($rank) }} 
                            <span>{{ formatSeconds($seconds) }} h</span>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">
                            Keine archivierten Stunden vorhanden.
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
