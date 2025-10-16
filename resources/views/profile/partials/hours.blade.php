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
    'ems-director' => 'Direktor',
    'assistant-ems-director' => 'Co. Direktor',
    'instructor' => 'Instruktor',
    'emergency-doctor' => 'Notarzt',
    'paramedic' => 'Rettungssanitäter',
    'emt' => 'Notfallsanitäter',
    'emt-trainee' => 'Azubi (EMT)',
    'praktikant' => 'Praktikant',
];
@endphp

{{-- Box: Aktive Stunden --}}
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

{{-- Box: Stundenarchiv nach Rang --}}
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

