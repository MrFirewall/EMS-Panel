@extends('layouts.app')
@section('title', 'Prüfungsversuche verwalten')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-list-alt nav-icon"></i> Alle Prüfungsversuche</h1>
            </div>
        </div>
    </div>
</div>

<div class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                {{-- Erfolgsmeldungen für Linkversand oder Reset --}}
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        <h5><i class="icon fas fa-check"></i> Erfolg!</h5>
                        {{ session('success') }}
                        
                        {{-- Zeigt den Link zur manuellen Kopie an --}}
                        @if (session('secure_url'))
                            <p class="mb-0 mt-2">
                                Link zum manuellen Kopieren: 
                                <code id="secure-link">{{ session('secure_url') }}</code>
                                <button class="btn btn-xs btn-outline-secondary ml-2" onclick="copyLink()">
                                    <i class="fas fa-copy"></i> Kopieren
                                </button>
                            </p>
                        @endif
                    </div>
                @endif

                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Übersicht über alle Versuche</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Prüfling</th>
                                    <th>Prüfung (Modul)</th>
                                    <th>Status</th>
                                    <th>Score</th>
                                    <th>Abgeschlossen am</th>
                                    <th style="width: 300px">Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($attempts as $attempt)
                                    <tr>
                                        <td>{{ $attempt->id }}</td>
                                        <td>{{ $attempt->user->name ?? 'Unbekannt' }}</td>
                                        <td>
                                            {{ $attempt->exam->title ?? 'N/A' }} 
                                            <small class="text-muted d-block">({{ $attempt->exam->trainingModule->name ?? 'N/A' }})</small>
                                        </td>
                                        <td>
                                            @if ($attempt->status === 'in_progress')
                                                <span class="badge badge-info">In Bearbeitung</span>
                                            @elseif ($attempt->status === 'submitted')
                                                <span class="badge badge-warning">Eingereicht</span>
                                            @elseif ($attempt->status === 'evaluated')
                                                @php $passed = $attempt->score >= $attempt->exam->pass_mark; @endphp
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
                                        <td>{{ $attempt->completed_at ? $attempt->completed_at->format('d.m.Y H:i') : 'N/A' }}</td>
                                        <td>
                                            {{-- 1. Ergebnis anzeigen --}}
                                            <a href="{{ route('exams.result', $attempt) }}" class="btn btn-sm btn-outline-info" title="Ergebnis ansehen / Bewerten">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            {{-- 2. Link senden (immer möglich, wenn nicht evaluated) --}}
                                            @can('sendLink', $attempt)
                                                @if ($attempt->status !== 'evaluated')
                                                    <form action="{{ route('admin.exams.send.link', $attempt) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-secondary" title="Link erneut senden">
                                                            <i class="fas fa-link"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            @endcan

                                            {{-- 3. Manuelle Bewertung (Nur für submitted oder in_progress) --}}
                                            @can('setEvaluated', $attempt)
                                                @if ($attempt->status !== 'evaluated')
                                                    <button type="button" class="btn btn-sm btn-outline-success" title="Manuell bewerten" data-toggle="modal" data-target="#evaluateModal{{ $attempt->id }}">
                                                        <i class="fas fa-clipboard-check"></i>
                                                    </button>
                                                @endif
                                            @endcan
                                            
                                            {{-- 4. Zurücksetzen (immer möglich, wenn nicht evaluated) --}}
                                            @can('resetAttempt', $attempt)
                                                @if ($attempt->status !== 'evaluated')
                                                    <form action="{{ route('admin.exams.reset.attempt', $attempt) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Versuch zurücksetzen" onclick="return confirm('Achtung: Alle Antworten werden gelöscht und der Link wird wieder nutzbar. Fortfahren?');">
                                                            <i class="fas fa-undo"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            @endcan
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer">
                        {{ $attempts->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@foreach ($attempts as $attempt)
    {{-- Modal für Manuelle Bewertung --}}
    <div class="modal fade" id="evaluateModal{{ $attempt->id }}" tabindex="-1" role="dialog" aria-labelledby="evaluateModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form action="{{ route('admin.exams.set.evaluated', $attempt) }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header bg-success">
                        <h5 class="modal-title" id="evaluateModalLabel">Bewertung für {{ $attempt->user->name }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>Prüfung: <strong>{{ $attempt->exam->title }}</strong></p>
                        <p>Mindestpunktzahl zum Bestehen: <strong>{{ $attempt->exam->pass_mark }}%</strong></p>
                        
                        <div class="form-group">
                            <label for="score">Gesamtpunktzahl in Prozent (0-100):</label>
                            <input type="number" name="score" id="score" class="form-control" min="0" max="100" value="{{ round($attempt->score ?? 0) }}" required>
                        </div>
                        <small class="text-muted">Hinweis: Setzen Sie die Punktzahl, basierend auf der automatischen Bewertung und der manuellen Bewertung der Freitextfelder. Der Status wird automatisch auf "Bewertet (evaluated)" gesetzt, wenn der Score die Mindestpunktzahl erreicht.</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Abbrechen</button>
                        <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Status setzen & Speichern</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endforeach


@endsection