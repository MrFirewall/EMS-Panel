@extends('layouts.app')

@section('title', 'Aktivitäten-Log')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <h1 class="m-0"><i class="fas fa-history me-2"></i> System-Aktivitäten-Log</h1>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        {{-- AdminLTE Card für die DataTable --}}
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">Alle protokollierten Aktionen</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    {{-- PHP-Hilfsfunktion für dynamische Badge-Farben --}}
                    @php
                        $getBadgeColor = function ($value, $isAction = false) {
                            $value = strtoupper($value);
                            if ($isAction) {
                                return match ($value) {
                                    'CREATED', 'LOGIN' => 'success',
                                    'UPDATED', 'LOGGED_IN' => 'warning',
                                    'DELETED', 'LOGOUT' => 'danger',
                                    default => 'info',
                                };
                            }
                            // Für Log-Typen
                            return match ($value) {
                                'EXAM', 'USER' => 'primary',
                                'DUTY_START', 'DUTY_END' => 'success',
                                default => 'secondary',
                            };
                        };
                    @endphp

                    <table id="activityLogTable" class="table table-bordered table-striped table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 150px;">Zeitpunkt</th>
                                <th>Akteur (Benutzer-ID)</th>
                                <th>Typ</th>
                                <th>Aktion</th>
                                <th>Beschreibung</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                                <tr>
                                    {{-- Spalte 1: Zeitpunkt --}}
                                    <td data-sort="{{ $log->created_at->timestamp }}">
                                        {{ $log->created_at->format('d.m.Y H:i:s') }}
                                    </td>
                                    
                                    {{-- Spalte 2: Akteur --}}
                                    <td>
                                        <strong>{{ $log->creator_name }}</strong>
                                        @if($log->user_id)
                                            <small class="text-muted d-block">ID: {{ $log->user_id }}</small>
                                        @endif
                                    </td>
                                    
                                    {{-- Spalte 3: Typ --}}
                                    <td>
                                        <span class="badge bg-{{ $getBadgeColor($log->log_type) }}">
                                            {{ $log->log_type }}
                                        </span>
                                    </td>
                                    
                                    {{-- Spalte 4: Aktion --}}
                                    <td>
                                        <span class="badge bg-{{ $getBadgeColor($log->action, true) }}">
                                            {{ $log->action }}
                                        </span>
                                    </td>
                                    
                                    {{-- Spalte 5: Beschreibung --}}
                                    <td class="text-wrap">
                                        {{ $log->description }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Keine Aktivitäten protokolliert.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            {{-- Footer (optional, hier nicht benötigt, da DataTables die Info selbst anzeigt) --}}
            <div class="card-footer clearfix d-none">
            </div>
        </div>
    </div>
@endsection


{{-- DATATABLES INITIALISIERUNG --}}
@push('scripts')
    <script>
        $(document).ready(function() {
            // DataTables auf die Tabelle anwenden
            $('#activityLogTable').DataTable({
                "paging": true,
                "lengthChange": true, // Option zur Änderung der angezeigten Einträge
                "searching": true,
                "ordering": true,
                "info": true,
                "autoWidth": false,
                "responsive": true,
                "order": [
                    [0, 'desc']
                ], // Standardmäßig nach Zeitpunkt absteigend sortieren

                // Deutsche Lokalisierung
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/German.json"
                },

                // Optional: Füge eine Spalte mit erweiterten Details hinzu
                // "columnDefs": [
                //     { "visible": false, "targets": 5 } // Details-Spalte verstecken, falls vorhanden
                // ]
            });
        });
    </script>
@endpush
