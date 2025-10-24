<div class="card card-primary card-outline mb-4">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-file-signature me-2"></i> Prüfungsergebnisse & Einstufungen</h3>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0 table-striped">
            <thead>
                <tr>
                    <th>Typ</th>
                    <th>Titel</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                {{-- Anzeige der automatisierten Prüfungsversuche --}}
                @forelse($examAttempts as $attempt)
                    @php
                        $statusColor = 'bg-secondary'; // Standardfarbe
                        $statusText = 'Unbekannt'; // Standardtext

                        // Status aus dem Enum übersetzen und Farbe setzen
                        if ($attempt->status === 'in_progress') {
                            $statusColor = 'bg-info';
                            $statusText = 'In Bearbeitung';
                        } elseif ($attempt->status === 'submitted') {
                            $statusColor = 'bg-warning';
                            $statusText = 'Eingereicht (Wartet auf Bewertung)';
                        } elseif ($attempt->status === 'evaluated') {
                            // Wenn bewertet, prüfe den finalen Modulstatus aus der Pivot-Tabelle
                            $moduleUser = $attempt->user->trainingModules->where('id', $attempt->exam->training_module_id)->first();
                            $finalStatus = $moduleUser->pivot->status ?? 'evaluated'; // Holt 'bestanden' / 'nicht_bestanden'

                            if ($finalStatus === 'bestanden') {
                                $statusColor = 'bg-success';
                                $statusText = 'Bestanden';
                            } elseif ($finalStatus === 'nicht_bestanden') {
                                $statusColor = 'bg-danger';
                                $statusText = 'Nicht bestanden';
                            } else {
                                // Fallback, falls Pivot-Status unerwartet ist, aber Versuch bewertet wurde
                                $statusColor = 'bg-secondary';
                                $statusText = 'Bewertet (Ergebnis unklar)';
                             }
                        }
                    @endphp
                    <tr>
                        <td><span class="badge bg-primary text-sm">Prüfung</span></td> {{-- Typ immer "Prüfung" --}}
                        <td>
                            <strong>{{ $attempt->exam->title ?? 'N/A' }}</strong>
                            {{-- Optional: Modulname anzeigen --}}
                            {{-- <small class="text-muted d-block">({{ $attempt->exam->trainingModule->name ?? 'N/A' }})</small> --}}
                        </td>
                        <td><span class="badge {{ $statusColor }}">{{ $statusText }}</span></td>
                    </tr>
                @empty
                    {{-- Wird unten behandelt, falls beide leer sind --}}
                @endforelse

                {{-- Fallback: Nur anzeigen, wenn BEIDE Listen leer sind --}}
                {{-- Annahme: $examinations ist eine andere Variable für manuelle Einstufungen? --}}
                @if($examAttempts->isEmpty() && (!isset($examinations) || $examinations->isEmpty()))
                    <tr>
                        <td colspan="3" class="text-center text-muted p-3">Keine Prüfungseinträge oder Modulabschlüsse vorhanden.</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
