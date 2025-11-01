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
                    <th>Ausbilder</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $passedAttempts = $examAttempts->filter(function($attempt) {
                        return $attempt->status === 'evaluated' && $attempt->exam && $attempt->score >= $attempt->exam->pass_mark;
                    });
                @endphp
                
                @forelse($passedAttempts as $attempt)
                    @php
                        $statusColor = 'bg-success';
                        $statusText = $attempt->evaluator->name ?? 'System';
                    @endphp
                    <tr>
                        <td><span class="badge bg-primary text-sm">Prüfung</span></td>
                        <td>
                            <strong>{{ $attempt->exam->title ?? 'N/A' }}</strong>
                           <small class="text-muted d-block">({{ $attempt->exam->description ?? 'N/A' }})</small>
                        </td>
                        <td><span class="badge {{ $statusColor }}">{{ $statusText }}</span></td>
                    </tr>
                @empty
                    {{-- Das @empty ist leer, WIE IM ORIGINAL --}}
                @endforelse

                {{-- Fallback: Angepasst an die NEUE $passedAttempts Variable --}}
                {{-- (Nimmt an, dass $examinations eine separate, vielleicht manuelle Liste ist) --}}
                @if($passedAttempts->isEmpty() && (!isset($examinations) || $examinations->isEmpty()))
                    <tr>
                        <td colspan="3" class="text-center text-muted p-3">Keine bestandenen Prüfungseinträge.</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
