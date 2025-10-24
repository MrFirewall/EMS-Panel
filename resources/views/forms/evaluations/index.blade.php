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
        {{-- KORRIGIERTER BLOCK: Zeigt die Erfolgsmeldung und den Link an --}}
        {{-- Prüft jetzt auf 'secure_url', die vom Controller gesendet wird --}}
        @if(session('secure_url'))
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                <h5><i class="icon fas fa-check"></i> Erfolg!</h5>
                {{-- Zeigt die Erfolgsmeldung aus dem Controller an --}}
                <p>{{ session('success') }}</p>
                {{-- Zeigt den Link aus 'secure_url' an --}}
                <input type="text" class="form-control" value="{{ session('secure_url') }}" readonly onclick="this.select(); document.execCommand('copy'); this.nextElementSibling.style.display = 'inline-block';" style="cursor: pointer;">
                <small class="text-muted" style="display: none;">Link wurde in die Zwischenablage kopiert!</small>
            </div>
        @endif

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
                                        <th>Betreff (Modul)</th>
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
                                            <td>{{ $antrag->json_data['module_name'] ?? 'N/A' }}</td>
                                            <td>{{ $antrag->created_at->format('d.m.Y') }}</td>
                                            <td>
                                                @if($antrag->evaluation_type === 'modul_anmeldung')
                                                    <form action="{{ route('admin.training.assign', ['user' => $antrag->user_id, 'module' => $antrag->json_data['module_id'], 'evaluation' => $antrag->id]) }}" method="POST" onsubmit="return confirm('Möchten Sie die Ausbildung für diesen Mitarbeiter wirklich starten?');">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-success" title="Mitarbeiter für das Modul freischalten">
                                                            <i class="fas fa-play-circle"></i> Ausbildung starten
                                                        </button>
                                                    </form>
                                                @elseif($antrag->evaluation_type === 'pruefung_anmeldung')
                                                    <form action="{{ route('admin.exams.attempts.store') }}" method="POST">
                                                        @csrf
                                                        <input type="hidden" name="user_id" value="{{ $antrag->user_id }}">
                                                        <input type="hidden" name="exam_id" value="{{ $antrag->json_data['exam_id'] ?? '' }}">
                                                        <input type="hidden" name="evaluation_id" value="{{ $antrag->id }}">
                                                        <button type="submit" class="btn btn-sm btn-info" title="Einen einmaligen Link für die Prüfung erstellen">
                                                            <i class="fas fa-link"></i> Prüfungslink generieren
                                                        </button>
                                                    </form>
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
