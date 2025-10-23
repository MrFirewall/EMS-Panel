@extends('layouts.app') {{-- Ersetze 'layouts.app' durch dein Admin-Layout --}}

@section('title', 'Benachrichtigungsregeln Verwalten')

{{-- Füge einen Stil-Block hinzu, um den Umbruch für Badges zu erzwingen --}}
@push('styles')
    <style>
        /* Erlaubt den Zeilenumbruch innerhalb der Badges */
        .badge-wrap {
            white-space: normal !important;
            word-break: break-word;
            display: inline-block; /* Wichtig, um die Breite der Zelle zu nutzen */
            text-align: left;
        }

        /* Stellt sicher, dass die Spaltenbreite flexibel ist und nicht unterdrückt wird */
        #rulesTable td {
            white-space: normal !important; 
        }
    </style>
@endpush

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
                    {{-- GEÄNDERT: 'nowrap' entfernt, da es den Umbruch verhindert --}}
                    <table id="rulesTable" class="table table-bordered table-striped table-hover dt-responsive" style="width:100%">
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
                            @foreach ($rules as $rule)
                                <tr>
                                    {{-- Spalte 0: Aktion(en) --}}
                                    <td>
                                        @if(is_array($rule->controller_action))
                                            {{-- GEÄNDERT: Füge die neue Klasse 'badge-wrap' hinzu --}}
                                            @foreach($rule->controller_action as $action)
                                                <span class="badge badge-info mb-1 badge-wrap">{{ $action }}</span>
                                            @endforeach
                                        @else
                                            <span class="badge badge-info badge-wrap">{{ $rule->controller_action }}</span>
                                        @endif
                                    </td>

                                    {{-- Spalte 1: Typ --}}
                                    <td>{{ ucfirst($rule->target_type) }}</td>

                                    {{-- Spalte 2: Identifier --}}
                                    <td>
                                        @if(!is_array($rule->target_identifier))
                                            {{-- Fallback, falls Daten noch nicht konvertiert wurden --}}
                                            <span class="badge badge-secondary badge-wrap">{{ $rule->target_identifier }}</span>
                                        @else
                                            {{-- Logik für 'user'-Typ --}}
                                            @if($rule->target_type === 'user')
                                                @foreach($rule->target_identifier as $identifier)
                                                    @if($identifier === 'triggering_user')
                                                        <span class="badge badge-primary mb-1 badge-wrap">Auslösender Benutzer</span>
                                                    @else
                                                        @php
                                                            $user = \App\Models\User::find($identifier);
                                                        @endphp
                                                        <span class="badge badge-primary mb-1 badge-wrap">
                                                            {{ $user?->name ?? 'Unbekannt' }} (ID: {{ $identifier }})
                                                        </span>
                                                    @endif
                                                @endforeach
                                                
                                            {{-- Logik für 'role' oder 'permission' --}}
                                            @elseif($rule->target_type === 'role' || $rule->target_type === 'permission')
                                                @foreach($rule->target_identifier as $identifier)
                                                    <span class="badge badge-success mb-1 badge-wrap">{{ $identifier }}</span>
                                                @endforeach
                                            
                                            {{-- Fallback für andere Typen --}}
                                            @else
                                                @foreach($rule->target_identifier as $identifier)
                                                    <span class="badge badge-light mb-1 badge-wrap">{{ $identifier }}</span>
                                                @endforeach
                                            @endif
                                        @endif
                                    </td>

                                    {{-- Spalte 3: Beschreibung (FEHLTE) --}}
                                    <td>
                                        {{-- Stellt sicher, dass die Zelle den Umbruch erlaubt --}}
                                        <div style="white-space: normal; max-width: 300px;">
                                            {{ $rule->event_description }}
                                        </div>
                                    </td>

                                    {{-- Spalte 4: Status (FEHLTE) --}}
                                    <td>
                                        @if($rule->is_active)
                                            <span class="badge badge-success">Aktiv</span>
                                        @else
                                            <span class="badge badge-danger">Inaktiv</span>
                                        @endif
                                    </td>

                                    {{-- Spalte 5: Aktionen (FEHLTE) --}}
                                    {{-- GEÄNDERT: white-space: nowrap auf die Zelle belassen, da Buttons nicht umbrechen sollen --}}
                                    <td class="text-right" style="white-space: nowrap;">
                                        @can('update', $rule)
                                            <a href="{{ route('admin.notification-rules.edit', $rule) }}" class="btn btn-xs btn-primary" title="Bearbeiten">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        @endcan
                                        @can('delete', $rule)
                                            <form action="{{ route('admin.notification-rules.destroy', $rule) }}" method="POST" class="d-inline" onsubmit="return confirm('Regel wirklich löschen?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-xs btn-danger" title="Löschen">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
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
            "responsive": true, // Aktiviert die Responsive-Erweiterung (wichtig)
            "lengthChange": false, 
            "autoWidth": false, // Deaktiviert die automatische Breitenanpassung (sehr wichtig für den Umbruch)
            "columnDefs": [
                { "width": "50%", "targets": 0 } // Versucht, der ersten Spalte mehr Platz zu geben
            ],
            "paging": true, 
            "searching": true, 
            "ordering": true, 
            "info": true,
            "language": {
                "url": "{{ asset('js/i18n/de-DE.json') }}"
            },
          });
        });
      </script>
@endpush