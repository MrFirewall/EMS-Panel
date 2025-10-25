@extends('layouts.app')
@section('title', 'Prüfungsversuche verwalten')

@section('content')
<div class="content-header">
<div class="container-fluid">
<div class="row mb-2">
<div class="col-sm-6">
<h1 class="m-0"><i class="fas fa-list-alt nav-icon"></i> Alle Prüfungsversuche</h1>
</div>
{{-- Optional: Button zum manuellen Erstellen eines Versuchs? --}}
{{-- <div class="col-sm-6 text-right">
    <a href="{{ route('admin.exams.attempts.create') }}" class="btn btn-info">Neuen Versuch manuell starten</a>
</div> --}}
</div>
</div>
</div>

<div class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                {{-- Erfolgsmeldungen --}}
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        <h5><i class="icon fas fa-check"></i> Erfolg!</h5>
                        {{ session('success') }}
                        @if (session('secure_url'))
                            <p class="mb-0 mt-2">
                                Link zum manuellen Kopieren:
                                <code id="secure-link">{{ session('secure_url') }}</code>
                                <button type="button" class="btn btn-xs btn-outline-secondary ml-2" onclick="copyLink()">
                                    <i class="fas fa-copy"></i> Kopieren
                                </button>
                            </p>
                        @endif
                    </div>
                @endif
                @if (session('error'))
                 <div class="alert alert-danger alert-dismissible">
                     <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                     <h5><i class="icon fas fa-ban"></i> Fehler!</h5>
                     {{ session('error') }}
                 </div>
                @endif

                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Übersicht über alle Versuche</h3>
                         {{-- Optional: Filter hinzufügen? --}}
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive"> {{-- Wichtig für kleinere Bildschirme --}}
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Prüfling</th>
                                        <th>Prüfung</th> {{-- Modul entfernt --}}
                                        <th>Status</th>
                                        <th>Score</th>
                                        <th>Gestartet</th>
                                        <th>Abgeschlossen</th>
                                        <th style="min-width: 210px;">Aktionen</th> {{-- Mindestbreite für Buttons --}}
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($attempts as $attempt)
                                        <tr>
                                            <td>{{ $attempt->id }}</td>
                                            <td>
                                                @if($attempt->user)
                                                <a href="{{ route('admin.users.show', $attempt->user) }}">{{ $attempt->user->name }}</a>
                                                @else
                                                <span class="text-muted">Unbekannt</span>
                                                @endif
                                            </td>
                                            <td>
                                                {{ $attempt->exam->title ?? 'N/A' }}
                                                {{-- Modulname entfernt --}}
                                            </td>
                                            <td>
                                                @if ($attempt->status === 'in_progress')
                                                    <span class="badge badge-info">In Bearbeitung</span>
                                                @elseif ($attempt->status === 'submitted')
                                                    <span class="badge badge-warning">Eingereicht</span>
                                                @elseif ($attempt->status === 'evaluated')
                                                    {{-- Ergebnis direkt aus Score ableiten --}}
                                                    @php $passed = optional($attempt->exam)->pass_mark !== null && $attempt->score >= $attempt->exam->pass_mark; @endphp
                                                    <span class="badge {{ $passed ? 'badge-success' : 'badge-danger' }}">Bewertet ({{ $passed ? 'Bestanden' : 'Nicht best.' }})</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($attempt->score !== null)
                                                    {{ $attempt->score }}%
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>{{ $attempt->started_at ? $attempt->started_at->format('d.m.Y H:i') : 'N/A' }}</td>
                                            <td>{{ $attempt->completed_at ? $attempt->completed_at->format('d.m.Y H:i') : 'N/A' }}</td>
                                            <td>
                                                <div class="btn-group"> {{-- Gruppiert die Buttons --}}
                                                    {{-- 1. Ergebnis anzeigen / Final bewerten --}}
                                                    <a href="{{ route('admin.exams.attempts.show', $attempt) }}" class="btn btn-sm btn-outline-info" title="Ergebnis ansehen / Bewerten">
                                                        <i class="fas fa-eye"></i>
                                                    </a>

                                                    {{-- 2. Link senden (wenn nicht bewertet) --}}
                                                    @can('sendLink', $attempt)
                                                        @if ($attempt->status !== 'evaluated')
                                                            <form action="{{ route('admin.exams.attempts.sendLink', $attempt) }}" method="POST" class="d-inline">
                                                                @csrf
                                                                <button type="submit" class="btn btn-sm btn-outline-secondary" title="Link erneut senden">
                                                                    <i class="fas fa-link"></i>
                                                                </button>
                                                            </form>
                                                        @endif
                                                    @endcan

                                                    {{-- 3. Manuelle Schnell-Bewertung (wenn nicht bewertet) --}}
                                                    @can('setEvaluated', $attempt)
                                                        @if ($attempt->status !== 'evaluated')
                                                            <button type="button" class="btn btn-sm btn-outline-success" title="Manuell bewerten" data-toggle="modal" data-target="#evaluateModal{{ $attempt->id }}">
                                                                <i class="fas fa-clipboard-check"></i>
                                                            </button>
                                                        @endif
                                                    @endcan

                                                    {{-- 4. Zurücksetzen (wenn nicht bewertet) --}}
                                                    @can('resetAttempt', $attempt)
                                                        @if ($attempt->status !== 'evaluated')
                                                            <form action="{{ route('admin.exams.attempts.reset', $attempt) }}" method="POST" class="d-inline">
                                                                @csrf
                                                                <button type="submit" class="btn btn-sm btn-outline-warning" title="Versuch zurücksetzen" onclick="return confirm('Achtung: Alle Antworten werden gelöscht und der Link wird wieder nutzbar. Fortfahren?');">
                                                                    <i class="fas fa-undo"></i>
                                                                </button>
                                                            </form>
                                                        @endif
                                                    @endcan

                                                    {{-- 5. Endgültig löschen --}}
                                                    @can('delete', $attempt)
                                                        <form action="{{ route('admin.exams.attempts.destroy', $attempt) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-danger" title="Versuch endgültig löschen" onclick="return confirm('ACHTUNG: Diese Aktion ist endgültig und kann nicht rückgängig gemacht werden. Alle Antworten und der Versuch werden gelöscht. Fortfahren?');">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </button>
                                                        </form>
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center p-4 text-muted">Es sind keine Prüfungsversuche vorhanden.</td> {{ Colspan angepasst }}
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @if ($attempts->hasPages())
                    <div class="card-footer">
                        {{ $attempts->links() }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modals für Schnellbewertung --}}
@foreach ($attempts as $attempt)
    @can('setEvaluated', $attempt)
    <div class="modal fade" id="evaluateModal{{ $attempt->id }}" tabindex="-1" role="dialog" aria-labelledby="evaluateModalLabel{{ $attempt->id }}" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form action="{{ route('admin.exams.attempts.setEvaluated', $attempt) }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header bg-success">
                        <h5 class="modal-title" id="evaluateModalLabel{{ $attempt->id }}">Bewertung für {{ $attempt->user->name ?? 'Unbekannt' }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>Prüfung: <strong>{{ $attempt->exam->title ?? 'N/A' }}</strong></p>
                        @if($attempt->exam)
                        <p>Mindestpunktzahl zum Bestehen: <strong>{{ $attempt->exam->pass_mark }}%</strong></p>
                        @endif

                        <div class="form-group">
                            <label for="score{{ $attempt->id }}">Gesamtpunktzahl in Prozent (0-100):</label>
                            <input type="number" name="score" id="score{{ $attempt->id }}" class="form-control" min="0" max="100" value="{{ round($attempt->score ?? 0) }}" required>
                             @error('score')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        {{-- Hinweis angepasst: Kein Modulstatus mehr --}}
                        <small class="text-muted">Hinweis: Setzt nur den Score und den Status des Versuchs auf 'evaluated'. Das Ergebnis (bestanden/nicht bestanden) wird daraus abgeleitet. Für die finale Bewertung (falls zusätzliche Schritte nötig sind), nutzen Sie die "Ansehen" <i class="fas fa-eye"></i> Funktion.</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Abbrechen</button>
                        <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Score setzen & Speichern</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endcan
@endforeach

@endsection

@push('scripts')
{{-- JavaScript zum Kopieren (unverändert) --}}
<script>
function copyLink() {
const linkElement = document.getElementById('secure-link');
if (linkElement) {
navigator.clipboard.writeText(linkElement.textContent.trim())
.then(() => {
alert('Prüfungslink wurde in die Zwischenablage kopiert!');
})
.catch(err => {
const tempInput = document.createElement('textarea');
tempInput.value = linkElement.textContent.trim();
document.body.appendChild(tempInput);
tempInput.select();
document.execCommand('copy');
document.body.removeChild(tempInput);
alert('Prüfungslink wurde in die Zwischenablage kopiert (Fallback)!');
});
}
}
</script>
@endpush
