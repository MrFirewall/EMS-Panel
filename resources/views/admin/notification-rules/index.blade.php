@extends('layouts.app') {{-- Ersetze 'layouts.app' durch dein Admin-Layout --}}

@section('title', 'Benachrichtigungsregeln Verwalten')

{{-- Füge DataTables CSS im Head-Bereich deines Layouts hinzu oder hier: --}}

@section('content')
    {{-- AdminLTE Content Header --}}
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><i class="nav-icon fas fa-cogs"></i> Benachrichtigungsregeln</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Benachrichtigungsregeln</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="content">
        <div class="container-fluid">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">Alle Regeln</h3>
                    <div class="card-tools">
                        @can('create', App\Models\NotificationRule::class)
                            <a href="{{ route('admin.notification-rules.create') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Neue Regel erstellen
                            </a>
                        @endcan
                    </div>
                </div>
                {{-- Card-Body ohne Padding, da DataTables sein eigenes Layout mitbringt --}}
                <div class="card-body">
                    {{-- ID hinzugefügt und DataTables-Klassen --}}
                    <table id="rulesTable" class="table table-bordered table-striped table-hover dt-responsive nowrap" style="width:100%">
                        <thead>
                            <tr>
                                <th>Aktion (Controller@Methode)</th>
                                <th>Ziel Typ</th>
                                <th>Ziel Identifier</th>
                                <th>Beschreibung</th>
                                <th>Status</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- @forelse loop without @empty --}}
                            @foreach ($rules as $rule)
                                <tr>
                                    <td>{{ $rule->controller_action }}</td>
                                    <td>{{ ucfirst($rule->target_type) }}</td>
                                    <td>
                                        {{-- Zeige Namen anstatt IDs für bessere Lesbarkeit --}}
                                        @if($rule->target_type === 'user')
                                            {{ \App\Models\User::find($rule->target_identifier)?->name ?? $rule->target_identifier }} (ID: {{ $rule->target_identifier }})
                                        @elseif($rule->target_type === 'role')
                                            {{ $rule->target_identifier }}
                                        @elseif($rule->target_type === 'permission')
                                             {{ $rule->target_identifier }}
                                        @else
                                            {{ $rule->target_identifier }}
                                        @endif
                                    </td>
                                    <td>{{ $rule->description ?? '-' }}</td>
                                    <td>
                                        @if ($rule->is_active)
                                            <span class="badge badge-success">Aktiv</span>
                                        @else
                                            <span class="badge badge-secondary">Inaktiv</span>
                                        @endif
                                    </td>
                                    <td>
                                        @can('update', $rule)
                                            <a href="{{ route('admin.notification-rules.edit', $rule) }}" class="btn btn-info btn-xs" title="Bearbeiten">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        @endcan
                                        @can('delete', $rule)
                                            <form action="{{ route('admin.notification-rules.destroy', $rule) }}" method="POST" style="display: inline-block;" onsubmit="return confirm('Sind Sie sicher, dass Sie diese Regel löschen möchten?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-xs" title="Löschen">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                            {{-- @empty block removed --}}
                        </tbody>
                    </table>
                </div>
                {{-- Laravel Pagination entfernt, DataTables übernimmt das --}}
            </div>
        </div>
    </div>
@endsection

{{-- Füge DataTables JS am Ende deines Layouts hinzu oder hier: --}}
@push('scripts')

    <script>
        $(function () {
          $("#rulesTable").DataTable({
            "responsive": true, // Aktiviert die Responsive-Erweiterung
            "lengthChange": false, // Deaktiviert die Auswahl der Eintragsanzahl
            "autoWidth": false, // Deaktiviert die automatische Breitenanpassung
            // "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"], // Beispiel für Buttons (benötigt zusätzliche JS-Dateien)
            "paging": true, // Aktiviert Paginierung
            "searching": true, // Aktiviert Suche
            "ordering": true, // Aktiviert Sortierung
            "info": true, // Aktiviert "Zeige X von Y Einträgen" Info
            "language": { // Optionale Übersetzung ins Deutsche
                  "decimal": ",",
                  "thousands": ".",
                  "info": "Zeige _START_ bis _END_ von _TOTAL_ Einträgen",
                  "infoEmpty": "Zeige 0 bis 0 von 0 Einträgen",
                  "infoFiltered": "(gefiltert von _MAX_ Einträgen)",
                  "infoPostFix": "",
                  "lengthMenu": "Zeige _MENU_ Einträge",
                  "loadingRecords": "Wird geladen...",
                  "processing": "Bitte warten...",
                  "search": "Suchen:",
                  "zeroRecords": "Keine Einträge gefunden", // Diese Meldung wird jetzt von DataTables angezeigt
                  "paginate": {
                      "first": "Erste",
                      "last": "Letzte",
                      "next": "Nächste",
                      "previous": "Zurück"
                  },
                  "aria": {
                      "sortAscending": ": aktivieren, um Spalte aufsteigend zu sortieren",
                      "sortDescending": ": aktivieren, um Spalte absteigend zu sortieren"
                  }
             }
          });//.buttons().container().appendTo('#rulesTable_wrapper .col-md-6:eq(0)'); // Für Buttons
        });
      </script>
@endpush

