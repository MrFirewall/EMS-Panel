@extends('layouts.app')

@section('title', 'Aktivitäten-Log')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <h1 class="m-0"><i class="fas fa-clipboard-list me-2"></i> Aktivitäten-Log</h1>
                </div>
            </div>
        </div>
    </div>

    {{-- AdminLTE Card für die Tabelle --}}
    <div class="card">
        <div class="card-header bg-primary">
            <h3 class="card-title">Alle System-Aktivitäten</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-sm mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Zeitpunkt</th>
                            <th>Akteur</th>
                            <th>Typ</th>
                            <th>Aktion</th>
                            <th>Beschreibung</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td>{{ $log->created_at->format('d.m.Y H:i') }}</td>
                                <td>
                                    {{ $log->creator_name }} 
                                    <small class="text-muted d-block">ID: {{ $log->user_id }}</small>
                                </td>
                                <td><span class="badge bg-secondary">{{ $log->log_type }}</span></td>
                                <td><span class="badge bg-info">{{ $log->action }}</span></td>
                                <td>{{ $log->description }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">Keine Aktivitäten protokolliert.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        {{-- KORRIGIERTE PAGINIERUNG FÜR ADMINLTE --}}
        <div class="card-footer clearfix">
            {{-- Zeigt die Ergebnisse an (z.B. Showing 1 to 10 of 20 results) --}}
            <div class="float-left">
                 @if ($logs->total() > 0)
                    Zeige {{ $logs->firstItem() }} bis {{ $logs->lastItem() }} von {{ $logs->total() }} Einträgen
                @endif
            </div>
            <div class="float-right">
                {{-- WICHTIG: Explizite Angabe der Bootstrap 4 View --}}
                {{ $logs->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>
@endsection

