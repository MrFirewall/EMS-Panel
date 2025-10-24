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
// Standardwerte für laufende oder eingereichte Prüfungen
//$statusColor = 'bg-secondary';
//$statusText = 'Zur Bewertung';

                    if ($attempt->status === 'submitted') {
                        $statusColor = 'bg-warning';
                        $statusText = 'Eingereicht';
                    }
                    
                    if ($attempt->status === 'evaluated') {
                        // Status aus der users_module Pivot-Tabelle abrufen
                        $moduleUser = $attempt->user->trainingModules->where('id', $attempt->exam->training_module_id)->first();
                        $finalStatus = $moduleUser->pivot->status ?? 'evaluated'; // Holt 'bestanden' / 'nicht_bestanden'

                        if ($finalStatus === 'bestanden') {
                            $statusColor = 'bg-success';
                            $statusText = 'Bestanden';
                        } elseif ($finalStatus === 'nicht_bestanden') {
                            $statusColor = 'bg-danger';
                            $statusText = 'Nicht bestanden';
                        }
                    }
                @endphp
                <tr>
                    <td><span class="badge {{ $statusColor }} text-sm">Prüfung</span></td>
                    <td>
                        <strong>{{ $attempt->exam->title ?? 'N/A' }}</strong>
                    </td>
                    <td><span class="badge {{ $statusColor }}">{{ $statusText }}</span></td>
                    
                </tr>
            @empty
                {{-- Wird unten behandelt, falls beide leer sind --}}
            @endforelse

            {{-- Fallback: Nur anzeigen, wenn BEIDE Listen leer sind --}}
            @if($examAttempts->isEmpty() && $examinations->isEmpty())
                <tr>
                    <td colspan="5" class="text-center text-muted">Keine Prüfungseinträge oder Modulabschlüsse vorhanden.</td>
                </tr>
            @endif
        </tbody>
    </table>
</div>


</div>
