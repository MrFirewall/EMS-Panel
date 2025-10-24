@extends('layouts.app')
@section('title', 'Formulare & Anträge')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-folder-open nav-icon"></i> Formulare & Anträge</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Formulare & Anträge</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="content">
    <div class="container-fluid">

        {{-- KORRIGIERTER BLOCK für Erfolgsmeldung und Link-Kopieren --}}
        @if(session('success') && session('secure_url'))
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                <h5><i class="icon fas fa-check"></i> Erfolg!</h5>
                {{-- Zeigt die allgemeine Erfolgsmeldung an --}}
                <p>{{ session('success') }}</p>
                {{-- Zeigt den Link mit Kopier-Button an --}}
                <div class="input-group mt-2">
                     {{-- ID hinzugefügt für JavaScript --}}
                    <input type="text" id="secure-link-input" class="form-control" value="{{ session('secure_url') }}" readonly>
                    <div class="input-group-append">
                        {{-- Button ruft jetzt die copyToClipboard Funktion auf --}}
                        <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('#secure-link-input')">
                            <i class="fas fa-copy"></i> Link kopieren
                        </button>
                    </div>
                </div>
                 <small id="copy-success-msg" class="text-muted" style="display: none;">Link wurde in die Zwischenablage kopiert!</small>
            </div>
        {{-- Fallback für andere Erfolgsmeldungen ohne Link --}}
        @elseif(session('success'))
             <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                <h5><i class="icon fas fa-check"></i> Erfolg!</h5>
                <p>{{ session('success') }}</p>
            </div>
        @endif
        {{-- Ende Korrektur --}}


        <div class="row">
            {{-- Spalte für offene Anträge --}}
            <div class="col-lg-12">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-inbox"></i> Offene Anträge</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Typ</th>
                                        <th>Antragsteller</th>
                                        <th>Betreff</th> {{-- Spaltenname vereinfacht --}}
                                        <th>Datum</th>
                                        <th>Aktion</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($offeneAntraege as $antrag)
                                        <tr>
                                            <td>
                                                <span class="badge {{ $antrag->evaluation_type === 'modul_anmeldung' ? 'bg-info' : 'bg-warning' }}">
                                                    {{ str_replace('_', ' ', ucfirst($antrag->evaluation_type)) }}
                                                </span>
                                            </td>
                                            <td>{{ $antrag->user->name ?? $antrag->target_name ?? 'Unbekannt' }}</td>
                                            <td>
                                                {{-- KORRIGIERT: Prüft den Typ und zeigt Modul- oder Prüfungsnamen an --}}
                                                @if($antrag->evaluation_type === 'modul_anmeldung')
                                                    {{ $antrag->json_data['module_name'] ?? 'N/A' }}
                                                @elseif($antrag->evaluation_type === 'pruefung_anmeldung')
                                                    {{ $antrag->json_data['exam_title'] ?? 'N/A' }} {{-- Zeigt jetzt exam_title --}}
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>{{ $antrag->created_at->format('d.m.Y') }}</td>
                                            <td>
                                                @if($antrag->evaluation_type === 'modul_anmeldung')
                                                    {{-- Stellt sicher, dass module_id existiert --}}
                                                    @if(isset($antrag->json_data['module_id']))
                                                        <form action="{{ route('admin.training.assign', ['user' => $antrag->user_id, 'module' => $antrag->json_data['module_id'], 'evaluation' => $antrag->id]) }}" method="POST" onsubmit="return confirm('Möchten Sie die Ausbildung für diesen Mitarbeiter wirklich starten?');">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-success" title="Mitarbeiter für das Modul freischalten">
                                                                <i class="fas fa-play-circle"></i> Ausbildung starten
                                                            </button>
                                                        </form>
                                                    @else
                                                         <span class="text-danger">Fehler: Modul-ID fehlt!</span>
                                                    @endif
                                                @elseif($antrag->evaluation_type === 'pruefung_anmeldung')
                                                     {{-- Stellt sicher, dass exam_id existiert --}}
                                                    @if(isset($antrag->json_data['exam_id']))
                                                        <form action="{{ route('admin.exams.attempts.store') }}" method="POST">
                                                            @csrf
                                                            <input type="hidden" name="user_id" value="{{ $antrag->user_id }}">
                                                            <input type="hidden" name="exam_id" value="{{ $antrag->json_data['exam_id'] }}"> {{-- Korrektes Feld --}}
                                                            <input type="hidden" name="evaluation_id" value="{{ $antrag->id }}">
                                                            <button type="submit" class="btn btn-sm btn-info" title="Einen einmaligen Link für die Prüfung erstellen">
                                                                <i class="fas fa-link"></i> Prüfungslink generieren
                                                            </button>
                                                        </form>
                                                     @else
                                                        <span class="text-danger">Fehler: Prüfungs-ID fehlt!</span>
                                                    @endif
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted p-3">
                                                Aktuell gibt es keine offenen Anträge.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Spalte für die letzten Bewertungen --}}
            <div class="col-lg-12 mt-4">
                <div class="card card-secondary card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-history"></i> Letzte eingereichte Bewertungen</h3>
                    </div>
                    <div class="card-body p-0">
                         <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Typ</th>
                                        <th>Bewertet für</th>
                                        <th>Bewertet von</th>
                                        <th>Datum</th>
                                        <th>Aktion</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($evaluations as $evaluation)
                                        <tr>
                                            <td>
                                                <span class="badge bg-secondary">
                                                     {{ str_replace('_', ' ', ucfirst($evaluation->evaluation_type)) }}
                                                </span>
                                            </td>
                                            <td>{{ $evaluation->user->name ?? $evaluation->target_name ?? 'N/A' }}</td>
                                            <td>{{ $evaluation->evaluator->name ?? 'N/A' }}</td>
                                            <td>{{ $evaluation->created_at->format('d.m.Y') }}</td>
                                            <td>
                                                <a href="{{ route('admin.forms.evaluations.show', $evaluation) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i> Details
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted p-3">
                                                Es wurden noch keine Bewertungen eingereicht.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                     @if ($evaluations->hasPages())
                        <div class="card-footer">
                            {{ $evaluations->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- NEUES JavaScript zum Kopieren des Links --}}
<script>
function copyToClipboard(elementSelector) {
    const inputElement = document.querySelector(elementSelector);
    if (!inputElement) return; // Element nicht gefunden

    // Moderne Methode mit Clipboard API (bevorzugt)
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(inputElement.value)
            .then(() => {
                // Erfolgsmeldung anzeigen (optional)
                const msgElement = document.getElementById('copy-success-msg');
                if (msgElement) {
                    msgElement.style.display = 'inline';
                    setTimeout(() => { msgElement.style.display = 'none'; }, 2000); // Nach 2 Sek. ausblenden
                }
                // alert('Link kopiert!'); // Alternative: Alert anzeigen
            })
            .catch(err => {
                console.error('Fehler beim Kopieren: ', err);
                // Fallback, falls Promise fehlschlägt
                fallbackCopyTextToClipboard(inputElement);
            });
    } else {
        // Fallback für ältere Browser
        fallbackCopyTextToClipboard(inputElement);
    }
}

// Fallback-Funktion für ältere Browser
function fallbackCopyTextToClipboard(inputElement) {
    inputElement.select(); // Text im Input auswählen
    try {
        const successful = document.execCommand('copy');
        if (successful) {
            const msgElement = document.getElementById('copy-success-msg');
            if (msgElement) {
                 msgElement.style.display = 'inline';
                 setTimeout(() => { msgElement.style.display = 'none'; }, 2000);
             }
             // alert('Link kopiert (Fallback)!');
        } else {
             alert('Kopieren fehlgeschlagen.');
        }
    } catch (err) {
        console.error('Fallback-Kopieren fehlgeschlagen: ', err);
        alert('Kopieren fehlgeschlagen.');
    }
}
</script>
@endpush
