@extends('layouts.app')
{{-- Titel angepasst --}}
@section('title', 'Anträge & Bewertungen')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                 {{-- Titel angepasst --}}
                <h1 class="m-0"><i class="fas fa-folder-open nav-icon"></i> Anträge & Bewertungen</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                     {{-- Breadcrumb angepasst --}}
                    <li class="breadcrumb-item active">Anträge & Bewertungen</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="content">
    <div class="container-fluid">

        {{-- Erfolgs-/Fehlermeldungen und Link-Kopieren (bleibt wie zuvor) --}}
        @if(session('success') && session('secure_url'))
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                <h5><i class="icon fas fa-check"></i> Erfolg!</h5>
                <p>{{ session('success') }}</p>
                <div class="input-group mt-2">
                    <input type="text" id="secure-link-input" class="form-control" value="{{ session('secure_url') }}" readonly>
                    <div class="input-group-append">
                        <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('#secure-link-input')">
                            <i class="fas fa-copy"></i> Link kopieren
                        </button>
                    </div>
                </div>
                 <small id="copy-success-msg" class="text-muted" style="display: none;">Link wurde in die Zwischenablage kopiert!</small>
            </div>
        @elseif(session('success'))
             <div class="alert alert-success alert-dismissible">
                 {{-- ... einfache Erfolgsmeldung ... --}}
             </div>
        @elseif(session('error'))
            <div class="alert alert-danger alert-dismissible">
                 {{-- ... Fehlermeldung ... --}}
            </div>
        @endif
        {{-- Ende Meldungen --}}

        {{-- Keine Tabs mehr, direkte Anzeige der Karten --}}
        <div class="row">
            {{-- Karte für ALLE Anträge --}}
            <div class="col-lg-12">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        {{-- Titel angepasst --}}
                        <h3 class="card-title"><i class="fas fa-file-signature mr-1"></i> Alle Anträge</h3>
                         {{-- Optional: Zähler für offene Anträge --}}
                         @php $pendingCount = $applications->where('status', 'pending')->count(); @endphp
                         @if($pendingCount > 0)<span class="badge badge-warning ml-2">{{ $pendingCount }} Offen</span>@endif
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th>Typ</th>
                                        <th>Antragsteller</th>
                                        <th>Betreff</th>
                                        <th>Datum</th>
                                        <th>Status</th> {{-- NEUE Spalte --}}
                                        <th>Aktion</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- Iteriere über $applications --}}
                                    @forelse($applications as $antrag)
                                        <tr>
                                            <td>
                                                <span class="badge {{ $antrag->evaluation_type === 'modul_anmeldung' ? 'bg-info' : 'bg-warning' }}">
                                                    {{ str_replace('_', ' ', ucfirst($antrag->evaluation_type)) }}
                                                </span>
                                            </td>
                                            <td>{{ $antrag->user->name ?? $antrag->target_name ?? 'Unbekannt' }}</td>
                                            <td>
                                                @if($antrag->evaluation_type === 'modul_anmeldung')
                                                    {{ $antrag->json_data['module_name'] ?? 'N/A' }}
                                                @elseif($antrag->evaluation_type === 'pruefung_anmeldung')
                                                    {{ $antrag->json_data['exam_title'] ?? 'N/A' }}
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>{{ $antrag->created_at->format('d.m.Y H:i') }}</td>
                                            <td>
                                                 {{-- Status anzeigen --}}
                                                 @if ($antrag->status === 'pending')
                                                    <span class="badge badge-warning">Offen</span>
                                                 @elseif ($antrag->status === 'processed')
                                                    <span class="badge badge-success">Bearbeitet</span>
                                                 @elseif ($antrag->status === 'rejected') {{-- Beispiel für abgelehnt --}}
                                                    <span class="badge badge-danger">Abgelehnt</span>
                                                 @else
                                                     <span class="badge badge-secondary">{{ ucfirst($antrag->status) }}</span>
                                                 @endif
                                            </td>
                                            <td>
                                                 {{-- Aktionen nur anzeigen, wenn der Antrag 'pending' ist UND der User Admin-Rechte hat --}}
                                                 @if($canViewAll && $antrag->status === 'pending')
                                                    @if($antrag->evaluation_type === 'modul_anmeldung')
                                                        @if(isset($antrag->json_data['module_id']))
                                                            @can('assignUser', \App\Models\TrainingModule::class)
                                                            <form action="{{ route('admin.training.assign', ['user' => $antrag->user_id, 'module' => $antrag->json_data['module_id'], 'evaluation' => $antrag->id]) }}" method="POST" onsubmit="return confirm('Ausbildung starten?');">
                                                                @csrf
                                                                <button type="submit" class="btn btn-xs btn-success" title="Ausbildung starten">
                                                                    <i class="fas fa-play-circle"></i> Starten
                                                                </button>
                                                            </form>
                                                            @endcan
                                                        @else
                                                             <span class="text-danger"><i class="fas fa-exclamation-triangle"></i> ID fehlt!</span>
                                                        @endif
                                                    @elseif($antrag->evaluation_type === 'pruefung_anmeldung')
                                                        @if(isset($antrag->json_data['exam_id']))
                                                            @can('generateExamLink', \App\Models\ExamAttempt::class)
                                                            <form action="{{ route('admin.exams.attempts.store') }}" method="POST">
                                                                @csrf
                                                                <input type="hidden" name="user_id" value="{{ $antrag->user_id }}">
                                                                <input type="hidden" name="exam_id" value="{{ $antrag->json_data['exam_id'] }}">
                                                                <input type="hidden" name="evaluation_id" value="{{ $antrag->id }}">
                                                                <button type="submit" class="btn btn-xs btn-info" title="Prüfungslink generieren">
                                                                    <i class="fas fa-link"></i> Link generieren
                                                                </button>
                                                            </form>
                                                            @endcan
                                                         @else
                                                            <span class="text-danger"><i class="fas fa-exclamation-triangle"></i> ID fehlt!</span>
                                                        @endif
                                                    @endif
                                                 @else
                                                    {{-- Für bearbeitete Anträge oder normale User keine Aktion anzeigen --}}
                                                     -
                                                 @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-center text-muted p-3">Keine Anträge gefunden.</td></tr> {{ Colspan erhöht }}
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    {{-- Paginierung für Anträge --}}
                    @if ($applications->hasPages())
                       <div class="card-footer clearfix"> {{ clearfix für float Paginierung }}
                           {{ $applications->appends(['evaluationsPage' => $evaluations->currentPage()])->links() }}
                       </div>
                   @endif
                </div>
            </div>

            {{-- Karte für die letzten Bewertungen --}}
            <div class="col-lg-12 mt-4">
                <div class="card card-secondary card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-history mr-1"></i> Letzte eingereichte Bewertungen</h3>
                    </div>
                    <div class="card-body p-0">
                         <div class="table-responsive">
                            <table class="table table-striped table-hover table-sm">
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
                                            <td><span class="badge bg-secondary">{{ str_replace('_', ' ', ucfirst($evaluation->evaluation_type)) }}</span></td>
                                            <td>{{ $evaluation->user->name ?? $evaluation->target_name ?? 'N/A' }}</td>
                                            <td>{{ $evaluation->evaluator->name ?? 'N/A' }}</td>
                                            <td>{{ $evaluation->created_at->format('d.m.Y H:i') }}</td>
                                            <td>
                                                @can('view', $evaluation)
                                                <a href="{{ route('admin.forms.evaluations.show', $evaluation) }}" class="btn btn-xs btn-outline-primary">
                                                    <i class="fas fa-eye"></i> Details
                                                </a>
                                                 @else
                                                     -
                                                 @endcan
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-muted p-3">Keine Bewertungen eingereicht.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                     @if ($evaluations->hasPages())
                        <div class="card-footer clearfix">
                            {{ $evaluations->appends(['applicationsPage' => $applications->currentPage()])->links() }}
                        </div>
                    @endif
                </div>
            </div>

             {{-- Tab-Inhalte für Module und Prüfungen entfernt --}}

        </div> {{-- /.row --}}
    </div> {{-- /.container-fluid --}}
</div> {{-- /.content --}}

{{-- Modal zum Generieren des Prüfungslinks (bleibt unverändert, wird aus Antrags-Tabelle getriggert) --}}
@can('generateExamLink', \App\Models\ExamAttempt::class)
<div class="modal fade" id="generateLinkModal" tabindex="-1" role="dialog" aria-labelledby="generateLinkModalLabel" aria-hidden="true">
   {{-- ... Modal Inhalt bleibt gleich ... --}}
    <div class="modal-dialog" role="document">
        <form action="{{ route('admin.exams.attempts.store') }}" method="POST">
            @csrf
            <input type="hidden" name="exam_id" id="modal_exam_id">
            {{-- evaluation_id könnte man hier optional über data-attribute übergeben, falls nötig --}}
            <div class="modal-content">
                <div class="modal-header bg-info">
                    <h5 class="modal-title" id="generateLinkModalLabel">Prüfungslink generieren für: <span id="modal_exam_title"></span></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="modal_user_id">Benutzer auswählen</label>
                        <select name="user_id" id="modal_user_id" class="form-control select2 @error('user_id', 'generateLinkErrorBag') is-invalid @enderror" style="width: 100%;" required>
                            <option value="">Bitte Benutzer auswählen...</option>
                            @foreach($usersForModal as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} (ID: {{ $user->id }})</option>
                            @endforeach
                        </select>
                         {{-- Error Handling für Modals --}}
                         @php $errorBagName = 'generateLinkErrorBag'; @endphp
                         @error('user_id', $errorBagName) <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
                         @error('exam_id', $errorBagName) <span class="text-danger d-block mt-2">{{ $message }}</span> @enderror
                    </div>
                    <p class="text-muted small">Generiert einen einmaligen Link für den ausgewählten Benutzer, um diese Prüfung abzulegen.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-info"><i class="fas fa-link mr-1"></i> Link generieren</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endcan

@endsection

@push('scripts')
{{-- JavaScript zum Kopieren des Links (bleibt unverändert) --}}
<script>
function copyToClipboard(elementSelector) {
    const inputElement = document.querySelector(elementSelector);
    if (!inputElement) return;

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(inputElement.value)
            .then(() => {
                const msgElement = document.getElementById('copy-success-msg');
                if (msgElement) {
                    msgElement.style.display = 'inline';
                    setTimeout(() => { msgElement.style.display = 'none'; }, 2000);
                }
            })
            .catch(err => {
                console.error('Fehler beim Kopieren: ', err);
                fallbackCopyTextToClipboard(inputElement);
            });
    } else {
        fallbackCopyTextToClipboard(inputElement);
    }
}

function fallbackCopyTextToClipboard(inputElement) {
    inputElement.select();
    try {
        const successful = document.execCommand('copy');
        const msgElement = document.getElementById('copy-success-msg');
        if (successful && msgElement) {
                 msgElement.style.display = 'inline';
                 setTimeout(() => { msgElement.style.display = 'none'; }, 2000);
        } else if (!successful) {
             alert('Kopieren fehlgeschlagen.');
        }
    } catch (err) {
        console.error('Fallback-Kopieren fehlgeschlagen: ', err);
        alert('Kopieren fehlgeschlagen.');
    }
}

// JavaScript für das "Link generieren"-Modal (bleibt unverändert)
$(document).ready(function() {
    // Select2 Initialisierung
    if ($.fn.select2) { // Prüfen ob Select2 geladen ist
        $('.select2').select2({
            theme: 'bootstrap4',
            dropdownParent: $('#generateLinkModal') // Wichtig für Modals
        });
    }

    $('#generateLinkModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var examId = button.data('exam-id');
        var examTitle = button.data('exam-title');
        var modal = $(this);
        modal.find('.modal-body #modal_exam_id').val(examId);
        modal.find('.modal-header #modal_exam_title').text(examTitle || 'Unbekannte Prüfung'); // Fallback für Titel
        modal.find('.modal-body #modal_user_id').val(null).trigger('change');
    });

     // Optional: Behandeln von Validierungsfehlern im Modal
     @if ($errors->hasBag('generateLinkErrorBag'))
         // Öffne das Modal wieder, wenn Fehler im spezifischen Error Bag sind
         // Beachte: exam_id und title müssen evtl. neu gesetzt werden (komplex)
         // $(document).ready(function() {
         //      $('#generateLinkModal').modal('show');
         // });
     @endif
});
</script>
@endpush
