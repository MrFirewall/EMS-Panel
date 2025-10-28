@extends('layouts.app')

@section('title', 'Benachrichtigungen')

@push('styles')

<style>
    /* Aktiv-Status für unsere neuen Filter-Links */
    .nav-pills .nav-link.active, .nav-pills .show>.nav-link {
        background-color: #007bff;
        color: #fff !important;
    }
    /* Stellt sicher, dass die Checkboxen in der Tabelle korrekt angezeigt werden */
    .mailbox-messages .icheck-primary>label {
        padding-left: 5px !important;
    }
    .mailbox-messages tr>td:first-child {
        width: 30px;
        text-align: center;
    }
</style>
@endpush

@section('content')

<section class="content">
    <div class="row">

        {{-- ================================================= --}}
        {{-- LINKE SPALTE (Filter) --}}
        {{-- ================================================= --}}
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Filter</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <ul class="nav nav-pills flex-column">
                        <li class="nav-item">
                            <a href="#" class="nav-link active" id="filter-all">
                                <i class="fas fa-inbox"></i> Alle
                                <span class="badge bg-secondary float-right">{{ $totalCount }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link" id="filter-unread">
                                <i class="fas fa-envelope"></i> Ungelesene
                                <span class="badge bg-primary float-right">{{ $unreadCount }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link" id="filter-read">
                                <i class="fas fa-envelope-open"></i> Gelesene
                                <span class="badge bg-light float-right text-dark">{{ $readCount }}</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Aktionen</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <ul class="nav nav-pills flex-column">
                        <li class="nav-item">
                            {{-- Formular für "Alle als gelesen markieren" --}}
                            <form action="{{ route('notifications.markAllRead') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-link nav-link text-left" {{ $unreadCount == 0 ? 'disabled' : '' }}>
                                    <i class="fas fa-check-double text-success"></i> Alle als gelesen markieren
                                </button>
                            </form>
                        </li>
                        <li class="nav-item">
                             {{-- Formular für "Alle gelesenen löschen" --}}
                            <form action="{{ route('notifications.clearRead') }}" method="POST" class="d-inline" onsubmit="return confirm('Möchten Sie wirklich ALLE gelesenen Benachrichtigungen löschen?');">
                                @csrf
                                <button type="submit" class="btn btn-link nav-link text-left text-danger" {{ $readCount == 0 ? 'disabled' : '' }}>
                                    <i class="far fa-trash-alt"></i> Alle Gelesenen löschen
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        {{-- ================================================= --}}
        {{-- RECHTE SPALTE (Tabelle)
        {{-- ================================================= --}}
        <div class="col-md-9">
            {{-- HINWEIS: Das <form> Tag wurde von hier entfernt --}}
                
            {{-- Die Hauptkarte im Stil der Mailbox --}}
            <div class="card card-primary card-outline">
                
                {{-- HINWEIS: Feedback-Meldungen --}}
                @if (session('success'))
                <div class="m-3">
                    <div class="alert alert-success alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        {{ session('success') }}
                    </div>
                </div>
                @endif

                {{-- OBERE STEUERLEISTE (Mailbox-Controls) --}}
                <div class="card-header">
                    <div class="mailbox-controls">
                        <button type="button" class="btn btn-default btn-sm checkbox-toggle" title="Alle auswählen/abwählen">
                            <i class="far fa-square"></i>
                        </button>
                        <div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm" id="bulk-destroy" title="Auswahl löschen">
                                <i class="far fa-trash-alt"></i>
                            </button>
                            <button type="button" class="btn btn-default btn-sm" id="bulk-mark-read" title="Auswahl als gelesen markieren">
                                <i class="far fa-envelope-open"></i>
                            </button>
                        </div>
                        
                        {{-- DataTables Paginierungs-Info (wird von JS hierher verschoben) --}}
                        <div class="float-right" id="datatable-info-platzhalter">
                        </div>
                    </div>
                </div>
                
                <div class="card-body p-0">
                    {{-- Die Tabelle im Mailbox-Stil --}}
                    <div class="table-responsive mailbox-messages">
                        
                        <table id="notificationsTable" class="table table-hover table-striped dt-responsive nowrap" style="width:100%">
                            <thead>
                                <tr>
                                    {{-- 0. Checkbox --}}
                                    <th class="no-sort no-search"></th>
                                    {{-- 1. Status (versteckt, nur zum Filtern) --}}
                                    <th class="d-none">Status</th> 
                                    {{-- 2. Benachrichtigung --}}
                                    <th>Benachrichtigung</th>
                                    {{-- 3. Zeitpunkt --}}
                                    <th style="width: 170px;">Zeitpunkt</th>
                                    {{-- 4. Aktion --}}
                                    <th class="no-sort no-search" style="width: 50px;">Aktion</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($allNotifications as $notification)
                                <tr class="{{ $notification->read_at ? '' : 'font-weight-bold' }}">
                                    <td>
                                        <div class="icheck-primary">
                                            <input type="checkbox" class="row-checkbox" name="notification_ids[]" value="{{ $notification->id }}" id="check_{{ $notification->id }}">
                                            <label for="check_{{ $notification->id }}"></label>
                                        </div>
                                    </td>
                                    <td class="d-none">
                                        {{ $notification->read_at ? 'Gelesen' : 'Neu' }}
                                    </td>
                                    <td class="mailbox-name">
                                        <a href="{{ $notification->data['url'] ?? '#' }}" class="text-dark">
                                            <i class="{{ $notification->data['icon'] ?? 'fas fa-bell' }} text-muted mr-2"></i>
                                            <span>{{ $notification->data['text'] ?? '...' }}</span>
                                        </a>
                                    </td>
                                    <td class="mailbox-date" data-order="{{ $notification->created_at->timestamp }}">
                                        {{ $notification->created_at->diffForHumans() }}
                                        <small class="d-block text-muted">{{ $notification->created_at->format('d.m.Y H:i') }}</H:i>
                                    </td>
                                    <td>
                                        <form action="{{ route('notifications.destroy', $notification->id) }}" method="POST" onsubmit="return confirm('Möchten Sie diese Benachrichtigung wirklich löschen?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-xs btn-default text-danger" title="Löschen">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td></td>
                                    <td class="d-none"></td>
                                    <td class="text-center text-muted">Keine Benachrichtigungen vorhanden.</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                
                {{-- UNTERE STEUERLEISTE (Mailbox-Controls) --}}
                <div class="card-footer p-0">
                     <div class="mailbox-controls p-3">
                        <button type="button" class="btn btn-default btn-sm checkbox-toggle" title="Alle auswählen/abwählen">
                            <i class="far fa-square"></i>
                        </button>
                        <div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm" id="bulk-destroy-footer" title="Auswahl löschen">
                                <i class="far fa-trash-alt"></i>
                            </button>
                            <button type="button" class="btn btn-default btn-sm" id="bulk-mark-read-footer" title="Auswahl als gelesen markieren">
                                <i class="far fa-envelope-open"></i>
                            </button>
                        </div>
                        
                        {{-- DataTables Paginierung (wird von JS hierher verschoben) --}}
                        <div class="float-right" id="datatable-pagination-platzhalter">
                        </div>
                    </div>
                </div>

            </div> {{-- /.card --}}
            {{-- HINWEIS: Das schließende </form> Tag wurde von hier entfernt --}}
        </div> {{-- /.col-md-9 --}}
    </div> {{-- /.row --}}
</section>
@endsection

@push('scripts')
{{-- DataTables & Abhängigkeiten (werden bereits in app.blade.php geladen) --}}
{{-- <script src="{{ asset('plugins/datatables/jquery.dataTables.min.js') }}"></script> ... --}}

<script>
    $(function () {
      
      var notificationsTable = $("#notificationsTable").DataTable({
        "language": {
            "url": "{{ asset('js/i18n/de-DE.json') }}" 
        },
        "order": [[3, 'desc']] , 
        "responsive": {
            details: {
                display: DataTable.Responsive.display.modal({
                    header: function (row) {
                        var data = row.data();
                        return 'Details für: ' + $(data[2]).find('span').text(); 
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
        "lengthChange": false, 
        "lengthMenu": [25, 50, -1],
        "pageLength": 25,
        "columnDefs": [ 
          { "targets": 'no-sort', "orderable": false },
          { "targets": 'no-search', "searchable": false },
          { "targets": 1, "visible": false }, // Status-Spalte
          { "responsivePriority": 1, "targets": 0 }, // Checkbox (Index 0)
          { "responsivePriority": 2, "targets": 2 }, // Benachrichtigung (Index 2)
          { "responsivePriority": 3, "targets": 4 }, // Aktion (Index 4)
          { "responsivePriority": 4, "targets": 3 }  // Zeitpunkt (Index 3)
        ],
        "dom":  "<'row'<'col-12't>>", 
        "infoCallback": function( settings, start, end, max, total, pre ) {
             $('#datatable-info-platzhalter').html(pre);
        }
      }); 

      $('#notificationsTable_paginate').appendTo('#datatable-pagination-platzhalter');

      // Optional: Globale Suche in den Header verschieben, wenn du sie brauchst
      // $('#notificationsTable_filter').find('label').addClass('m-0');
      // $('#notificationsTable_filter').find('input').attr('placeholder', 'Suche...').addClass('form-control-sm');
      // $('#notificationsTable_filter').appendTo('#datatable-search-platzhalter'); 
      // Aktuell ist die globale Suche aktiv, aber nicht sichtbar (da "dom" sie entfernt hat)

      // ======================================================
      // JS für LINKE FILTER-SPALTE
      // ======================================================
      var $filterLinks = $('.nav-pills a[id^="filter-"]');
      
      $filterLinks.on('click', function(e) {
          e.preventDefault();
          $filterLinks.removeClass('active');
          $(this).addClass('active');

          var filterValue = '';
          var column = notificationsTable.column(1); // Spalte 1 = Status

          if (this.id === 'filter-unread') {
              filterValue = '^Neu$'; 
          } else if (this.id === 'filter-read') {
              filterValue = '^Gelesen$';
          }
          
          column.search(filterValue, true, false).draw();
      });

      // ======================================================
      // JS für BULK-AKTIONEN (NEUE VERSION OHNE FORM-TAG)
      // ======================================================
      
      var $table = $('#notificationsTable');

      // 1. "Alle auswählen" Button
      $('.checkbox-toggle').click(function () {
            var clicks = $(this).data('clicks');
            var $icon = $(this).find('i');
            // WICHTIG: .rows({ search: 'applied' }) stellt sicher, dass nur die
            // Zeilen ausgewählt werden, die dem aktuellen Filter (z.B. "Ungelesene") entsprechen
            var $checkboxes = notificationsTable.rows({ search: 'applied' }).nodes().to$().find('.row-checkbox');
            
            if (clicks) {
                $checkboxes.prop('checked', false);
                $icon.removeClass('fa-check-square').addClass('fa-square');
            } else {
                $checkboxes.prop('checked', true);
                $icon.removeClass('fa-square').addClass('fa-check-square');
            }
            $(this).data('clicks', !clicks);
      });
      
      // 2. Klick-Handler für die Bulk-Buttons
      $('#bulk-destroy, #bulk-mark-read, #bulk-destroy-footer, #bulk-mark-read-footer').on('click', function(e) {
          e.preventDefault();
          var action = this.id.includes('destroy') ? 'destroy' : 'mark_read';

          // Finde alle gecheckten Checkboxen
          var selectedIds = notificationsTable.rows({ search: 'applied' }).nodes().to$().find('.row-checkbox:checked')
              .map(function() { return $(this).val(); })
              .get();

          if (selectedIds.length === 0) {
              alert('Bitte wählen Sie zuerst eine oder mehrere Benachrichtigungen aus.');
              return;
          }

          var url = (action === 'destroy') 
              ? '{{ route('notifications.bulkDestroy') }}' 
              : '{{ route('notifications.bulkMarkRead') }}';
          
          var confirmation = (action === 'destroy')
              ? 'Möchten Sie die ' + selectedIds.length + ' ausgewählten Benachrichtigungen wirklich löschen?'
              : 'Möchten Sie die ' + selectedIds.length + ' ausgewählten Benachrichtigungen als gelesen markieren?';

          if (!confirm(confirmation)) {
              return;
          }

          // Dynamisch ein Formular erstellen, absenden und entfernen
          var $tempForm = $('<form>', {
              'method': 'POST',
              'action': url
          }).appendTo('body');

          // CSRF-Token hinzufügen
          $tempForm.append($('<input>', {
              'type': 'hidden',
              'name': '_token',
              'value': '{{ csrf_token() }}'
          }));

          // Alle IDs hinzufügen
          $.each(selectedIds, function(index, id) {
              $tempForm.append($('<input>', {
                  'type': 'hidden',
                  'name': 'notification_ids[]',
                  'value': id
              }));
          });

          // Absenden
          $tempForm.submit();
      });
      
      // 3. Status des "Alle auswählen"-Buttons zurücksetzen, wenn Tabelle neu gezeichnet wird
      notificationsTable.on('draw.dt', function() {
           $('.checkbox-toggle').data('clicks', false);
           $('.checkbox-toggle').find('i').removeClass('fa-check-square').addClass('fa-square');
      });

    });
</script>
@endpush