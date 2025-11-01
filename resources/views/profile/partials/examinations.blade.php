<div class="card card-primary card-outline mb-4">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-file-signature me-2"></i> Prüfungsergebnisse</h3>
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
                            
                            // --- KORRIGIERTE LOGIK START ---
                            // Prüfe, ob die Relationen geladen sind, um Fehler zu vermeiden
                            if ($attempt->exam) {
                                // Vergleiche den Score des Versuchs direkt mit der Bestehensgrenze der Prüfung
                                if ($attempt->score >= $attempt->exam->pass_mark) {
                                    $statusColor = 'bg-success';
                                    $statusText = 'Bestanden';
                                } else {
                                    $statusColor = 'bg-danger';
                                    $statusText = 'Nicht bestanden';
                                }
                            } else {
                                // Fallback, falls 'exam' Relation fehlt (sollte nicht passieren)
                                $statusColor = 'bg-dark';
                                $statusText = 'Bewertet (Fehler)';
                            }
                            // --- KORRIGIERTE LOGIK ENDE ---
                            
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
                        <td colspan="3" class="text-center text-muted p-3">Keine Prüfungseinträge.</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
