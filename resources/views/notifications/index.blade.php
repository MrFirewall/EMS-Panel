@extends('layouts.app')

@section('title', 'Benachrichtigungs-Archiv')

{{-- DataTables CSS --}}
@push('styles')
<link rel="stylesheet" href="{{ asset('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
<link rel="stylesheet" href="{{ asset('plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Benachrichtigungs-Archiv</h3>
                    
                    {{-- Nur anzeigen, wenn es ungelesene gibt --}}
                    @if($unreadCount > 0)
                    <div class="card-tools">
                        <form action="{{ route('notifications.markAllRead') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success">
                                <i class="fas fa-check-double mr-1"></i>
                                Alle als gelesen markieren ({{ $unreadCount }})
                            </button>
                        </form>
                    </div>
                    @endif
                </div>

                {{-- START: Angepasster Card-Body mit DataTable --}}
                <div class="card-body">
                    <table id="notificationsTable" class="table table-bordered table-striped dt-responsive nowrap" style="width:100%">
                        <thead>
                            <tr>
                                {{-- Klassen für JS-Targeting (siehe unten) --}}
                                <th class="no-sort no-search" style="width: 20px;"></th> {{-- Icon --}}
                                <th style="width: 100px;">Status</th>
                                <th>Benachrichtigung</th>
                                <th style="width: 170px;">Zeitpunkt</th>
                                <th class="no-sort no-search" style="width: 50px;">Aktion</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($allNotifications as $notification)
                                {{-- Zeile fett markieren, wenn ungelesen --}}
                                <tr class="{{ $notification->read_at ? '' : 'font-weight-bold' }}">
                                    <td>
                                        <i class="{{ $notification->data['icon'] ?? 'fas fa-bell' }} text-muted"></i>
                                    </td>
                                    <td>
                                        @if($notification->read_at)
                                            <span class="badge badge-light">Gelesen</span>
                                        @else
                                            <span class="badge badge-primary">Neu</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ $notification->data['url'] ?? '#' }}" class="text-dark">
                                            <span>{{ $notification->data['text'] ?? '...' }}</span>
                                        </a>
                                    </td>
                                    {{-- data-order nutzt den Timestamp für korrekte Sortierung --}}
                                    <td data-order="{{ $notification->created_at->timestamp }}">
                                        {{ $notification->created_at->diffForHumans() }}
                                        <br>
                                        <small class="text-muted">{{ $notification->created_at->format('d.m.Y H:i') }} Uhr</small>
                                    </td>
                                    <td>
                                        <form action="{{ route('notifications.destroy', $notification->id) }}" method="POST" onsubmit="return confirm('Möchten Sie diese Benachrichtigung wirklich löschen?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-xs btn-danger" title="Löschen">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">
                                        Keine Benachrichtigungen vorhanden.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{-- END: Angepasster Card-Body --}}
                
                {{-- Paginierungs-Links entfernt, da DataTables dies übernimmt --}}
            </div>

        </div>
    </div>
</div>
@endsection

{{-- DataTables JS --}}
@push('scripts')
<script src="{{ asset('plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ asset('plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
<script src="{{ asset('plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>

<script>
    $(function () {
      // ID von #usersTable zu #notificationsTable geändert
      $("#notificationsTable").DataTable({
        "language": {
            // Sicherstellen, dass dieser Pfad in deinem /public-Ordner existiert
            "url": "{{ asset('js/i18n/de-DE.json') }}" 
        },
        // Nach Spalte 3 (Zeitpunkt) absteigend sortieren
        "order": [[3, 'desc']] , 
        "responsive": {
            details: {
                display: DataTable.Responsive.display.modal({
                    header: function (row) {
                        var data = row.data();
                        // data[2] ist der Benachrichtigungstext
                        return 'Details für: ' + $(data[2]).text(); // .text() entfernt HTML
                    }
                }),
                renderer: DataTable.Responsive.renderer.tableAll({
                    tableClass: 'table'
                })
            }
        },
        "autoWidth": true,
        "paging": true,
        "ordering": true,
        "info": true,        
        "searching": true,         
        "lengthChange": true,
        "lengthMenu": [10, 25, 50, -1],
        // Spalten ausschließen, die wir oben mit den Klassen markiert haben
        "columnDefs": [ {
            "targets": 'no-sort',
            "orderable": false
          },
          {
            "targets": 'no-search',
            "searchable": false
        }],
        "layout": {
            bottomEnd: {
                paging: {
                    firstLast: false
                }
            }
        }
      });
    });
</script>
@endpush