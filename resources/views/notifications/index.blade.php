@extends('layouts.app')

@section('title', 'Benachrichtigungen')

@push('styles')
{{-- NEU: iCheck-Bootstrap für Mailbox-Checkboxen (aus dem AdminLTE Beispiel) --}}
<link rel="stylesheet" href="{{ asset('plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">
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

{{-- Wir verwenden jetzt die Sektion-Struktur aus dem AdminLTE-Beispiel --}}
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
                            {{-- Dieser Link filtert die Tabelle --}}
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
            {{-- WICHTIG: Das Formular umschließt die gesamte Logik --}}
            <form id="bulkActionForm" method="POST">
                @csrf
                
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
                                        {{-- Zeile fett markieren, wenn ungelesen --}}
                                        <tr class="{{ $notification->read_at ? '' : 'font-weight-bold' }}">
                                            
                                            {{-- 0. Checkbox (im iCheck-Stil) --}}
                                            <td>
                                                <div class="icheck-primary">
                                                    <input type="checkbox" class="row-checkbox" name="notification_ids[]" value="{{ $notification->id }}" id="check_{{ $notification->id }}">
                                                    <label for="check_{{ $notification->id }}"></label>
                                                </div>
                                            </td>
                                            
                                            {{-- 1. Status (versteckt) --}}
                                            <td class="d-none">
                                                {{ $notification->read_at ? 'Gelesen' : 'Neu' }}
                                            </td>

                                            {{-- 2. Benachrichtigung (mit Icon) --}}
                                            <td class="mailbox-name">
                                                <a href="{{ $notification->data['url'] ?? '#' }}" class="text-dark">
                                                    <i class="{{ $notification->data['icon'] ?? 'fas fa-bell' }} text-muted mr-2"></i>
                                                    <span>{{ $notification->data['text'] ?? '...' }}</span>
                                                </a>
                                            </td>

                                            {{-- 3. Zeitpunkt --}}
                                            <td class="mailbox-date" data-order="{{ $notification->created_at->timestamp }}">
                                                {{ $notification->created_at->diffForHumans() }}
                                                <small class="d-block text-muted">{{ $notification->created_at->format('d.m.Y H:i') }}</H:i>
                                            </td>
                                            
                                            {{-- 4. Aktion (Löschen) --}}
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
                                            <td colspan="5" class="text-center text-muted">
                                                Keine Benachrichtigungen vorhanden.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>

                        </div> {{-- /.mailbox-messages --}}
                    </div> {{-- /.card-body --}}
                    
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
            </form> {{-- Ende des Formulars --}}
        </div> {{-- /.col-md-9 --}}
    </div> {{-- /.row --}}
</section>
@endsection

{{-- DataTables JS --}}
@push('scripts')

<script>
    $(function () {
      
      // DataTables-Initialisierung
      var notificationsTable = $("#notificationsTable").DataTable({
        "language": {
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
        "searching": true, // Globale Suche bleibt aktiv        
        "lengthChange": false, // Wir verwenden Paging, aber nicht die "Zeige X Einträge" Auswahl
        "lengthMenu": [25, 50, -1],
        "pageLength": 25,

        // Spalten-Definitionen (Sort/Search deaktivieren)
        "columnDefs": [ 
          { "targets": 'no-sort', "orderable": false },
          { "targets": 'no-search', "searchable": false },
          // Verstecke die Status-Spalte (Index 1)
          { "targets": 1, "visible": false }
        ],

        // NEU: Layout anpassen (DOM) - Wir verschieben die Elemente
        "dom":  "<'row'<'col-12't>>", // 't' = nur die Tabelle
        "infoCallback": function( settings, start, end, max, total, pre ) {
            // Verschiebt die Info-Anzeige (z.B. "1 bis 25 von 100") in unseren Platzhalter
             $('#datatable-info-platzhalter').html(pre);
        }

      }); // Ende DataTable Initialisierung

      // Verschiebe die Paginierung in unseren Platzhalter im Footer
      $('#notificationsTable_paginate').appendTo('#datatable-pagination-platzhalter');

      // Verschiebe die globale Suche (aus dem Standard-Layout) in den Card-Header
      $('#notificationsTable_filter').find('label').addClass('m-0');
      $('#notificationsTable_filter').find('input').attr('placeholder', 'Suche...').addClass('form-control-sm');
      // $('#notificationsTable_filter').appendTo('#datatable-search-platzhalter'); // Falls du einen Platzhalter im Header hättest


      // ======================================================
      // NEU: JS für LINKE FILTER-SPALTE
      // ======================================================
      var $filterLinks = $('.nav-pills a[id^="filter-"]');
      
      $filterLinks.on('click', function(e) {
          e.preventDefault();
          
          // Setze alle Links zurück
          $filterLinks.removeClass('active');
          // Aktiviere den geklickten Link
          $(this).addClass('active');

          var filterValue = '';
          var column = notificationsTable.column(1); // Spalte 1 = Status

          if (this.id === 'filter-unread') {
              // Suche exakt nach "Neu"
              filterValue = '^Neu$'; 
          } else if (this.id === 'filter-read') {
              // Suche exakt nach "Gelesen"
              filterValue = '^Gelesen$';
          }
          
          // Wende den Filter an (Regex, kein Smart-Search) und zeichne neu
          column.search(filterValue, true, false).draw();
      });

      // ======================================================
      // JS für BULK-AKTIONEN (leicht angepasst)
      // ======================================================
      
      var $table = $('#notificationsTable');
      var $form = $('#bulkActionForm');

      // 1. "Alle auswählen" Button (AdminLTE-Stil)
      $('.checkbox-toggle').click(function () {
            var clicks = $(this).data('clicks');
            var $icon = $(this).find('i');
            var $checkboxes = notificationsTable.rows({ search: 'applied' }).nodes().to$().find('.row-checkbox');
            
            if (clicks) {
                //Uncheck all checkboxes
                $checkboxes.prop('checked', false);
                $icon.removeClass('fa-check-square').addClass('fa-square');
            } else {
                //Check all checkboxes
                $checkboxes.prop('checked', true);
                $icon.removeClass('fa-square').addClass('fa-check-square');
            }
            $(this).data('clicks', !clicks);
      });
      
      // 2. Aktionen ausführen (Event-Handler für alle Buttons)
      $('#bulk-destroy, #bulk-mark-read, #bulk-destroy-footer, #bulk-mark-read-footer').on('click', function(e) {
          e.preventDefault();
          var action = this.id.includes('destroy') ? 'destroy' : 'mark_read';

          if (action === "destroy") {
                if (!confirm('Möchten Sie die ausgewählten Benachrichtigungen wirklich löschen?')) {
                    return;
                }
                $form.attr('action', '{{ route('notifications.bulkDestroy') }}');
          } else {
                 if (!confirm('Möchten Sie die ausgewählten Benachrichtigungen wirklich als gelesen markieren?')) {
                    return;
                }
                $form.attr('action', '{{ route('notifications.bulkMarkRead') }}');
          }
          
          // Formular absenden
          $form.submit();
      });
      
      // 3. Status des "Alle auswählen"-Buttons zurücksetzen, wenn Tabelle neu gezeichnet wird
      notificationsTable.on('draw.dt', function() {
           $('.checkbox-toggle').data('clicks', false);
           $('.checkbox-toggle').find('i').removeClass('fa-check-square').addClass('fa-square');
      });

    });
</script>
@endpush