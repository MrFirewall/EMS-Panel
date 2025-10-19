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
                                    <th style="width: 150px">Aktionen</th>
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
                                                <span class="badge badge-success">Bewertet</span>
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
                                            {{-- Link zur Detailansicht (Ergebnis) --}}
                                            <a href="{{ route('exams.result', $attempt) }}" class="btn btn-sm btn-outline-info" title="Ergebnis ansehen">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            {{-- Reset-Funktion (nur wenn nicht in Bearbeitung) --}}
                                            @can('resetAttempt', $attempt)
                                                @if ($attempt->status !== 'in_progress')
                                                    <form action="{{ route('admin.exams.reset.attempt', $attempt) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Versuch zurücksetzen" onclick="return confirm('Sicher? Alle Antworten werden gelöscht und der Link wird wieder nutzbar.');">
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
@endsection