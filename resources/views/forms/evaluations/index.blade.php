@extends('layouts.app')
{{-- Titel angepasst --}}
@section('title', 'Übersicht: Formulare & Verwaltung')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                 {{-- Titel angepasst --}}
                <h1 class="m-0"><i class="fas fa-tachometer-alt nav-icon"></i> Übersicht</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                     {{-- Breadcrumb angepasst --}}
                    <li class="breadcrumb-item active">Übersicht</li>
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
                {{-- ... Code für Erfolgsmeldung mit Link ... --}}
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
                 <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                 <h5><i class="icon fas fa-check"></i> Erfolg!</h5>
                 <p>{{ session('success') }}</p>
             </div>
        @elseif(session('error'))
            <div class="alert alert-danger alert-dismissible">
                 <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                 <h5><i class="icon fas fa-ban"></i> Fehler!</h5>
                 <p>{{ session('error') }}</p>
            </div>
        @endif
        {{-- Ende Meldungen --}}

        {{-- Card mit Tabs wiederhergestellt --}}
        <div class="card card-primary card-tabs">
            <div class="card-header p-0 pt-1 border-bottom-0"> {{-- border-bottom-0 hinzugefügt --}}
                <ul class="nav nav-tabs" id="overview-tabs" role="tablist">
                    {{-- Tab 1: Alle Anträge --}}
                    <li class="nav-item">
                        <a class="nav-link active" id="tab-applications-link" data-toggle="pill" href="#tab-applications" role="tab" aria-controls="tab-applications" aria-selected="true">
                           <i class="fas fa-file-signature mr-1"></i> Alle Anträge
                           @php $pendingCount = $applications->where('status', 'pending')->count(); @endphp
                           @if($pendingCount > 0)<span class="badge badge-warning ml-1">{{ $pendingCount }} Offen</span>@endif
                        </a>
                    </li>
                     {{-- Tab 2: Letzte Bewertungen --}}
                    <li class="nav-item">
                        <a class="nav-link" id="tab-recent-evaluations-link" data-toggle="pill" href="#tab-recent-evaluations" role="tab" aria-controls="tab-recent-evaluations" aria-selected="false">
                           <i class="fas fa-history mr-1"></i> Letzte Bewertungen
                        </a>
                    </li>
                    {{-- Tab 3: Module (nur wenn Berechtigung) --}}
                    @can('training.view')
                    <li class="nav-item">
                        <a class="nav-link" id="tab-training-modules-link" data-toggle="pill" href="#tab-training-modules" role="tab" aria-controls="tab-training-modules" aria-selected="false">
                           <i class="fas fa-graduation-cap mr-1"></i> Ausbildungsmodule
                        </a>
                    </li>
                    @endcan
                    {{-- Tab 4: Prüfungen (nur wenn Berechtigung) --}}
                    @can('exams.manage')
                    <li class="nav-item">
                        <a class="nav-link" id="tab-exams-link" data-toggle="pill" href="#tab-exams" role="tab" aria-controls="tab-exams" aria-selected="false">
                           <i class="fas fa-file-alt mr-1"></i> Prüfungen
                        </a>
                    </li>
                     @endcan
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="overview-tabs-content">

                    {{-- Tab-Inhalt 1: Alle Anträge --}}
                    <div class="tab-pane fade show active" id="tab-applications" role="tabpanel" aria-labelledby="tab-applications-link">
                        <h4>Alle Anträge</h4>
                         <div class="table-responsive">
                            <table class="table table-striped table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th>Typ</th>
                                        <th>Antragsteller</th>
                                        <th>Betreff</th>
                                        <th>Datum</th>
                                        <th>Status</th>
                                        <th>Aktion</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($applications as $antrag)
                                        <tr>
                                            <td><span class="badge {{ $antrag->evaluation_type === 'modul_anmeldung' ? 'bg-info' : 'bg-warning' }}">{{ str_replace('_', ' ', ucfirst($antrag->evaluation_type)) }}</span></td>
                                            <td>{{ $antrag->user->name ?? $antrag->target_name ?? 'Unbekannt' }}</td>
                                            <td>
                                                @if($antrag->evaluation_type === 'modul_anmeldung')
                                                    {{ $antrag->json_data['module_name'] ?? 'N/A' }}
                                                @elseif($antrag->evaluation_type === 'pruefung_anmeldung')
                                                    {{ $antrag->json_data['exam_title'] ?? 'N/A' }}
                                                @else N/A @endif
                                            </td>
                                            <td>{{ $antrag->created_at->format('d.m.Y H:i') }}</td>
                                            <td>
                                                 @if ($antrag->status === 'pending') <span class="badge badge-warning">Offen</span>
                                                 @elseif ($antrag->status === 'processed') <span class="badge badge-success">Bearbeitet</span>
                                                 @elseif ($antrag->status === 'rejected') <span class="badge badge-danger">Abgelehnt</span>
                                                 @else <span class="badge badge-secondary">{{ ucfirst($antrag->status) }}</span> @endif
                                            </td>
                                            <td>
                                                 @if($canViewAll && $antrag->status === 'pending')
                                                    {{-- Aktionen für Modulanmeldung --}}
                                                    @if($antrag->evaluation_type === 'modul_anmeldung' && isset($antrag->json_data['module_id']))
                                                        @can('assignUser', \App\Models\TrainingModule::class)
                                                        <form action="{{ route('admin.training.assign', ['user' => $antrag->user_id, 'module' => $antrag->json_data['module_id'], 'evaluation' => $antrag->id]) }}" method="POST" onsubmit="return confirm('Ausbildung starten?');"> @csrf <button type="submit" class="btn btn-xs btn-success" title="Ausbildung starten"><i class="fas fa-play-circle"></i> Starten</button></form>
                                                        @endcan
                                                    {{-- Aktionen für Prüfungsanmeldung --}}
                                                    @elseif($antrag->evaluation_type === 'pruefung_anmeldung' && isset($antrag->json_data['exam_id']))
                                                        @can('generateExamLink', \App\Models\ExamAttempt::class)
                                                        <form action="{{ route('admin.exams.attempts.store') }}" method="POST"> @csrf <input type="hidden" name="user_id" value="{{ $antrag->user_id }}"><input type="hidden" name="exam_id" value="{{ $antrag->json_data['exam_id'] }}"><input type="hidden" name="evaluation_id" value="{{ $antrag->id }}"><button type="submit" class="btn btn-xs btn-info" title="Prüfungslink generieren"><i class="fas fa-link"></i> Link</button></form>
                                                        @endcan
                                                    @endif
                                                    {{-- Link zur Detailansicht des Antrags --}}
                                                     <a href="{{ route('admin.forms.evaluations.show', $antrag) }}" class="btn btn-xs btn-outline-primary ml-1" title="Antrag Details ansehen"><i class="fas fa-eye"></i></a>
                                                 @else
                                                     {{-- Link zur Detailansicht für normale User oder bearbeitete Anträge --}}
                                                     @can('view', $antrag)
                                                        <a href="{{ route('admin.forms.evaluations.show', $antrag) }}" class="btn btn-xs btn-outline-primary" title="Details ansehen"><i class="fas fa-eye"></i></a>
                                                     @else
                                                        -
                                                     @endcan
                                                 @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-center text-muted p-3">Keine Anträge gefunden.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        {{-- Paginierung für Anträge --}}
                        @if ($applications->hasPages())
                           <div class="card-footer clearfix bg-light border-top-0"> {{-- Angepasste Klassen für Footer --}}
                               {{ $applications->appends(['evaluationsPage' => $evaluations->currentPage(), 'modulesPage' => $trainingModules->currentPage(), 'examsPage' => $exams->currentPage()])->links() }}
                           </div>
                       @endif
                    </div>

                    {{-- Tab-Inhalt 2: Letzte Bewertungen --}}
                    <div class="tab-pane fade" id="tab-recent-evaluations" role="tabpanel" aria-labelledby="tab-recent-evaluations-link">
                         <h4>Letzte eingereichte Bewertungen</h4>
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
                                                 @else - @endcan
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-muted p-3">Keine Bewertungen eingereicht.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                         {{-- Paginierung für Bewertungen --}}
                         @if ($evaluations->hasPages())
                            <div class="card-footer clearfix bg-light border-top-0">
                                {{ $evaluations->appends(['applicationsPage' => $applications->currentPage(), 'modulesPage' => $trainingModules->currentPage(), 'examsPage' => $exams->currentPage()])->links() }}
                            </div>
                        @endif
                    </div>

                    {{-- Tab-Inhalt 3: Ausbildungsmodule --}}
                    @can('training.view')
                    <div class="tab-pane fade" id="tab-training-modules" role="tabpanel" aria-labelledby="tab-training-modules-link">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                             <h4>Alle Ausbildungsmodule</h4>
                             @can('create', \App\Models\TrainingModule::class)
                            <a href="{{ route('modules.create') }}" class="btn btn-sm btn-success">
                                <i class="fas fa-plus mr-1"></i> Neues Modul erstellen
                            </a>
                            @endcan
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Kategorie</th>
                                        <th>Zugewiesene User</th> {{-- NEU --}}
                                        <th>Aktionen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($trainingModules as $module)
                                        <tr>
                                            <td>{{ $module->name }}</td>
                                            <td>{{ $module->category ?? '-' }}</td>
                                            <td>
                                                <span class="badge badge-pill badge-info">{{ $module->users_count }}</span>
                                            </td>
                                            <td>
                                                 <div class="btn-group">
                                                    {{-- NEU: Link zur User-Verwaltungsseite (Route muss erstellt werden!) --}}
                                                    @can('assignUser', $module) {{-- Policy anpassen/erstellen --}}
                                                        {{-- === AUSKOMMENTIERT BIS ROUTE EXISTIERT === --}}
                                                        {{-- <a href="{{ route('admin.modules.assignments.index', $module) }}" class="btn btn-xs btn-outline-secondary" title="Benutzerzuweisungen verwalten">
                                                            <i class="fas fa-users-cog"></i> Verwalten
                                                        </a> --}}
                                                        {{-- === ENDE AUSKOMMENTIERT === --}}
                                                    @endcan
                                                    <a href="{{ route('modules.show', $module) }}" class="btn btn-xs btn-outline-primary" title="Details ansehen"><i class="fas fa-eye"></i></a>
                                                     @can('update', $module)
                                                        <a href="{{ route('modules.edit', $module) }}" class="btn btn-xs btn-outline-warning" title="Modul bearbeiten"><i class="fas fa-edit"></i></a>
                                                     @endcan
                                                      @can('delete', $module)
                                                        <form action="{{ route('modules.destroy', $module) }}" method="POST" class="d-inline" onsubmit="return confirm('Modul wirklich löschen?');"> @csrf @method('DELETE') <button type="submit" class="btn btn-xs btn-outline-danger" title="Modul löschen"><i class="fas fa-trash"></i></button></form>
                                                      @endcan
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted p-3">Keine Ausbildungsmodule gefunden.</td></tr> {{-- Colspan angepasst --}}
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        {{-- Paginierung für Module --}}
                         @if ($trainingModules->hasPages())
                            <div class="card-footer clearfix bg-light border-top-0">
                                {{ $trainingModules->appends(['applicationsPage' => $applications->currentPage(), 'evaluationsPage' => $evaluations->currentPage(), 'examsPage' => $exams->currentPage()])->links() }}
                            </div>
                        @endif
                    </div>
                    @endcan

                    {{-- Tab-Inhalt 4: Prüfungen --}}
                     @can('exams.manage')
                    <div class="tab-pane fade" id="tab-exams" role="tabpanel" aria-labelledby="tab-exams-link">
                         <div class="d-flex justify-content-between align-items-center mb-3">
                             <h4>Alle Prüfungen</h4>
                              @can('create', \App\Models\Exam::class)
                                <a href="{{ route('admin.exams.create') }}" class="btn btn-sm btn-success">
                                    <i class="fas fa-plus mr-1"></i> Neue Prüfung erstellen
                                </a>
                              @endcan
                         </div>
                         <div class="table-responsive">
                            <table class="table table-striped table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th>Titel</th>
                                        <th>Bestehensgrenze</th>
                                        <th>Fragen</th>
                                        <th>Aktionen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($exams as $exam)
                                    <tr>
                                        <td>{{ $exam->title }}</td>
                                        <td>{{ $exam->pass_mark }}%</td>
                                        <td>{{ $exam->questions_count }}</td>
                                        <td>
                                            <div class="btn-group">
                                                @can('generateExamLink', \App\Models\ExamAttempt::class)
                                                <button type="button" class="btn btn-xs btn-info" data-toggle="modal" data-target="#generateLinkModal" data-exam-id="{{ $exam->id }}" data-exam-title="{{ $exam->title }}" title="Prüfungslink für Benutzer generieren">
                                                    <i class="fas fa-link"></i> Link generieren
                                                </button>
                                                @endcan
                                                @can('view', $exam)
                                                <a href="{{ route('admin.exams.show', $exam) }}" class="btn btn-xs btn-outline-primary" title="Details ansehen"><i class="fas fa-eye"></i></a>
                                                @endcan
                                                 @can('update', $exam)
                                                <a href="{{ route('admin.exams.edit', $exam) }}" class="btn btn-xs btn-outline-warning" title="Prüfung bearbeiten"><i class="fas fa-edit"></i></a>
                                                 @endcan
                                                  @can('delete', $exam)
                                                <form action="{{ route('admin.exams.destroy', $exam) }}" method="POST" class="d-inline" onsubmit="return confirm('Prüfung wirklich löschen? Alle zugehörigen Versuche gehen verloren!');"> @csrf @method('DELETE') <button type="submit" class="btn btn-xs btn-outline-danger" title="Prüfung löschen"><i class="fas fa-trash"></i></button></form>
                                                  @endcan
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted p-3">Keine Prüfungen gefunden.</td></tr> {{-- Colspan angepasst --}}
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        {{-- Paginierung für Prüfungen --}}
                         @if ($exams->hasPages())
                            <div class="card-footer clearfix bg-light border-top-0">
                                {{ $exams->appends(['applicationsPage' => $applications->currentPage(), 'evaluationsPage' => $evaluations->currentPage(), 'modulesPage' => $trainingModules->currentPage()])->links() }}
                            </div>
                        @endif
                    </div>
                     @endcan

                </div> {{-- /.tab-content --}}
            </div> {{-- /.card-body --}}
        </div> {{-- /.card --}}
    </div> {{-- /.container-fluid --}}
</div> {{-- /.content --}}

{{-- Modal zum Generieren des Prüfungslinks (bleibt unverändert) --}}
@can('generateExamLink', \App\Models\ExamAttempt::class)
<div class="modal fade" id="generateLinkModal" tabindex="-1" role="dialog" aria-labelledby="generateLinkModalLabel" aria-hidden="true">
   {{-- ... Modal Inhalt bleibt gleich ... --}}
    <div class="modal-dialog" role="document">
        <form action="{{ route('admin.exams.attempts.store') }}" method="POST">
            @csrf
            <input type="hidden" name="exam_id" id="modal_exam_id">
            <div class="modal-content">
                <div class="modal-header bg-info">
                    <h5 class="modal-title" id="generateLinkModalLabel">Prüfungslink generieren für: <span id="modal_exam_title"></span></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
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
function copyToClipboard(elementSelector) { /* ... */ }
function fallbackCopyTextToClipboard(inputElement) { /* ... */ }

// JavaScript für das "Link generieren"-Modal (bleibt unverändert)
$(document).ready(function() {
    if ($.fn.select2) {
        $('.select2').select2({ theme: 'bootstrap4', dropdownParent: $('#generateLinkModal') });
    }
    $('#generateLinkModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var examId = button.data('exam-id');
        var examTitle = button.data('exam-title');
        var modal = $(this);
        modal.find('.modal-body #modal_exam_id').val(examId);
        modal.find('.modal-header #modal_exam_title').text(examTitle || 'Unbekannte Prüfung');
        modal.find('.modal-body #modal_user_id').val(null).trigger('change');
    });

    // Behalten, um Fehler im Modal anzuzeigen, falls implementiert
    @if ($errors->hasBag('generateLinkErrorBag'))
       // $('#generateLinkModal').modal('show');
    @endif

    // NEU: Stellt sicher, dass der richtige Tab nach dem Neuladen aktiv ist (falls Paginierung genutzt wird)
    var activeTab = localStorage.getItem('activeEvaluationTab');
    if (activeTab) {
        $('#overview-tabs a[href="' + activeTab + '"]').tab('show');
    }
    // Speichere den aktiven Tab beim Wechseln
    $('a[data-toggle="pill"]').on('shown.bs.tab', function (e) {
        localStorage.setItem('activeEvaluationTab', $(e.target).attr('href'));
    });

});
</script>
@endpush
