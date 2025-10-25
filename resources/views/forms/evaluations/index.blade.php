@extends('layouts.app')
{{-- Titel angepasst --}}
@section('title', 'Übersicht: Anträge, Bewertungen, Module & Prüfungen')

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

        {{-- Card mit Tabs --}}
        <div class="card card-primary card-tabs">
            <div class="card-header p-0 pt-1">
                <ul class="nav nav-tabs" id="overview-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="tab-pending-applications-link" data-toggle="pill" href="#tab-pending-applications" role="tab" aria-controls="tab-pending-applications" aria-selected="true">
                           <i class="fas fa-inbox mr-1"></i> Offene Anträge <span class="badge badge-warning ml-1">{{ $offeneAntraege->count() }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-recent-evaluations-link" data-toggle="pill" href="#tab-recent-evaluations" role="tab" aria-controls="tab-recent-evaluations" aria-selected="false">
                           <i class="fas fa-history mr-1"></i> Letzte Bewertungen
                        </a>
                    </li>
                    @can('training.view') {{-- Nur anzeigen, wenn Berechtigung vorhanden --}}
                    <li class="nav-item">
                        <a class="nav-link" id="tab-training-modules-link" data-toggle="pill" href="#tab-training-modules" role="tab" aria-controls="tab-training-modules" aria-selected="false">
                           <i class="fas fa-graduation-cap mr-1"></i> Ausbildungsmodule
                        </a>
                    </li>
                    @endcan
                    @can('exams.manage') {{-- Nur anzeigen, wenn Berechtigung vorhanden --}}
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

                    {{-- Tab 1: Offene Anträge --}}
                    <div class="tab-pane fade show active" id="tab-pending-applications" role="tabpanel" aria-labelledby="tab-pending-applications-link">
                        <h4>Offene Anträge</h4>
                         <div class="table-responsive">
                            <table class="table table-striped table-hover table-sm"> {{-- table-sm für kompaktere Ansicht --}}
                                <thead>
                                    <tr>
                                        <th>Typ</th>
                                        <th>Antragsteller</th>
                                        <th>Betreff</th>
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
                                                 {{-- Nur Admins sehen die Buttons --}}
                                                 @if($canViewAll)
                                                    @if($antrag->evaluation_type === 'modul_anmeldung')
                                                        @if(isset($antrag->json_data['module_id']))
                                                            @can('assignUser', \App\Models\TrainingModule::class) {{-- Policy Check --}}
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
                                                            @can('generateExamLink', \App\Models\ExamAttempt::class) {{-- Policy Check --}}
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
                                                      <span class="text-muted">Wartet auf Bearbeitung</span>
                                                 @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-muted p-3">Keine offenen Anträge.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Tab 2: Letzte Bewertungen --}}
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
                                                {{-- Link zur Admin-Detailansicht, falls berechtigt --}}
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
                         {{-- Paginierung für Bewertungen --}}
                         @if ($evaluations->hasPages())
                            <div class="mt-3 d-flex justify-content-center">
                                {{ $evaluations->appends(['modulesPage' => $trainingModules->currentPage(), 'examsPage' => $exams->currentPage()])->links() }}
                            </div>
                        @endif
                    </div>

                    {{-- Tab 3: Ausbildungsmodule --}}
                    @can('training.view')
                    <div class="tab-pane fade" id="tab-training-modules" role="tabpanel" aria-labelledby="tab-training-modules-link">
                        <h4>Alle Ausbildungsmodule</h4>
                        <div class="mb-3 text-right">
                             @can('create', \App\Models\TrainingModule::class) {{-- Berechtigung prüfen --}}
                            <a href="{{ route('modules.create') }}" class="btn btn-sm btn-success">
                                <i class="fas fa-plus mr-1"></i> Neues Modul erstellen
                            </a>
                            @endcan
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Kategorie</th>
                                        <th>Beschreibung</th>
                                        <th>Aktionen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($trainingModules as $module)
                                        <tr>
                                            <td>{{ $module->id }}</td>
                                            <td>{{ $module->name }}</td>
                                            <td>{{ $module->category ?? '-' }}</td>
                                            <td>{{ Str::limit($module->description, 50) }}</td>
                                            <td>
                                                 <div class="btn-group">
                                                     {{-- Details ansehen (für alle mit training.view) --}}
                                                    <a href="{{ route('modules.show', $module) }}" class="btn btn-xs btn-outline-info" title="Details ansehen"><i class="fas fa-eye"></i></a>
                                                     {{-- Bearbeiten (nur mit training.edit) --}}
                                                     @can('update', $module)
                                                        <a href="{{ route('modules.edit', $module) }}" class="btn btn-xs btn-outline-warning" title="Modul bearbeiten"><i class="fas fa-edit"></i></a>
                                                     @endcan
                                                      {{-- Löschen (nur mit training.delete) --}}
                                                      @can('delete', $module)
                                                        <form action="{{ route('modules.destroy', $module) }}" method="POST" class="d-inline" onsubmit="return confirm('Modul wirklich löschen?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-xs btn-outline-danger" title="Modul löschen"><i class="fas fa-trash"></i></button>
                                                        </form>
                                                      @endcan
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-muted p-3">Keine Ausbildungsmodule gefunden.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        {{-- Paginierung für Module --}}
                         @if ($trainingModules->hasPages())
                            <div class="mt-3 d-flex justify-content-center">
                                {{-- Wichtig: Andere Paginator-Parameter anhängen, damit die Tabs funktionieren --}}
                                {{ $trainingModules->appends(['evaluationsPage' => $evaluations->currentPage(), 'examsPage' => $exams->currentPage()])->links() }}
                            </div>
                        @endif
                    </div>
                    @endcan

                    {{-- Tab 4: Prüfungen --}}
                     @can('exams.manage')
                    <div class="tab-pane fade" id="tab-exams" role="tabpanel" aria-labelledby="tab-exams-link">
                         <h4>Alle Prüfungen</h4>
                          <div class="mb-3 text-right">
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
                                        <th>ID</th>
                                        <th>Titel</th>
                                        <th>Bestehensgrenze</th>
                                        <th>Fragen</th>
                                        <th>Aktionen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($exams as $exam)
                                    <tr>
                                        <td>{{ $exam->id }}</td>
                                        <td>{{ $exam->title }}</td>
                                        <td>{{ $exam->pass_mark }}%</td>
                                        <td>{{ $exam->questions_count }}</td>
                                        <td>
                                            <div class="btn-group">
                                                {{-- Link generieren Button --}}
                                                @can('generateExamLink', \App\Models\ExamAttempt::class)
                                                <button type="button" class="btn btn-xs btn-info" data-toggle="modal" data-target="#generateLinkModal" data-exam-id="{{ $exam->id }}" data-exam-title="{{ $exam->title }}" title="Prüfungslink für Benutzer generieren">
                                                    <i class="fas fa-link"></i> Link generieren
                                                </button>
                                                @endcan
                                                {{-- Details ansehen --}}
                                                @can('view', $exam)
                                                <a href="{{ route('admin.exams.show', $exam) }}" class="btn btn-xs btn-outline-primary" title="Details ansehen"><i class="fas fa-eye"></i></a>
                                                @endcan
                                                 {{-- Bearbeiten --}}
                                                 @can('update', $exam)
                                                <a href="{{ route('admin.exams.edit', $exam) }}" class="btn btn-xs btn-outline-warning" title="Prüfung bearbeiten"><i class="fas fa-edit"></i></a>
                                                 @endcan
                                                  {{-- Löschen --}}
                                                  @can('delete', $exam)
                                                <form action="{{ route('admin.exams.destroy', $exam) }}" method="POST" class="d-inline" onsubmit="return confirm('Prüfung wirklich löschen? Alle zugehörigen Versuche gehen verloren!');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-xs btn-outline-danger" title="Prüfung löschen"><i class="fas fa-trash"></i></button>
                                                </form>
                                                  @endcan
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-muted p-3">Keine Prüfungen gefunden.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        {{-- Paginierung für Prüfungen --}}
                         @if ($exams->hasPages())
                            <div class="mt-3 d-flex justify-content-center">
                                {{ $exams->appends(['evaluationsPage' => $evaluations->currentPage(), 'modulesPage' => $trainingModules->currentPage()])->links() }}
                            </div>
                        @endif
                    </div>
                     @endcan

                </div> {{-- /.tab-content --}}
            </div> {{-- /.card-body --}}
        </div> {{-- /.card --}}
    </div> {{-- /.container-fluid --}}
</div> {{-- /.content --}}

{{-- Modal zum Generieren des Prüfungslinks --}}
@can('generateExamLink', \App\Models\ExamAttempt::class)
<div class="modal fade" id="generateLinkModal" tabindex="-1" role="dialog" aria-labelledby="generateLinkModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form action="{{ route('admin.exams.attempts.store') }}" method="POST">
            @csrf
            <input type="hidden" name="exam_id" id="modal_exam_id">
            {{-- evaluation_id wird hier nicht benötigt, da direkt generiert --}}
            {{-- <input type="hidden" name="evaluation_id" value=""> --}}
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
                        <select name="user_id" id="modal_user_id" class="form-control select2 @error('user_id') is-invalid @enderror" style="width: 100%;" required>
                            <option value="">Bitte Benutzer auswählen...</option>
                            @foreach($usersForModal as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} (ID: {{ $user->id }})</option>
                            @endforeach
                        </select>
                         @error('user_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                         {{-- Fehlermeldung für exam_id, falls nötig --}}
                         @error('exam_id') <span class="text-danger d-block mt-2">{{ $message }}</span> @enderror
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
{{-- JavaScript zum Kopieren des Links (bleibt wie zuvor) --}}
<script>
function copyToClipboard(elementSelector) {
    // ... (Funktion bleibt gleich) ...
}
function fallbackCopyTextToClipboard(inputElement) {
    // ... (Funktion bleibt gleich) ...
}

// NEU: JavaScript für das "Link generieren"-Modal
$(document).ready(function() {
    // Select2 Initialisierung (stelle sicher, dass Select2 geladen ist)
    $('.select2').select2({
        theme: 'bootstrap4', // Optional: AdminLTE Theme
        dropdownParent: $('#generateLinkModal') // Wichtig, damit das Dropdown im Modal korrekt angezeigt wird
    });

    // Wenn das Modal geöffnet wird, setze die Exam-ID und den Titel
    $('#generateLinkModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget); // Button that triggered the modal
        var examId = button.data('exam-id');
        var examTitle = button.data('exam-title');

        var modal = $(this);
        modal.find('.modal-body #modal_exam_id').val(examId);
        modal.find('.modal-header #modal_exam_title').text(examTitle);
        // Setze das User-Dropdown zurück, wenn das Modal geöffnet wird
        modal.find('.modal-body #modal_user_id').val(null).trigger('change');
    });

     // Optional: Behandeln von Validierungsfehlern im Modal
     @if ($errors->has('user_id') || $errors->has('exam_id')) // Prüft auf Fehler, die wahrscheinlich aus dem Modal kamen
         // Wenn ein Fehler auftritt, öffne das Modal erneut (braucht ggf. Anpassung, um die richtige Exam-ID zu behalten)
         // $('#generateLinkModal').modal('show');
         // Es ist oft einfacher, Fehler oberhalb des Formulars anzuzeigen, statt das Modal wieder zu öffnen.
     @endif

});
</script>
@endpush
