@extends('layouts.app')

@section('title', 'Benachrichtigungs-Archiv')

{{-- DataTables CSS --}}
@push('styles')
<link rel="stylesheet" href="{{ asset('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
<link rel="stylesheet" href="{{ asset('plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
<style>
    /* Stellt sicher, dass der "Alle auswählen"-Checkbox zentriert ist */
    #notificationsTable thead th:first-child {
        text-align: center;
    }
    /* NEU: Filter-Inputs stylen */
    #notificationsTable tfoot th {
        padding: 5px;
    }
    #notificationsTable tfoot input,
    #notificationsTable tfoot select {
        width: 100%;
        box-sizing: border-box;
        font-weight: normal;
        font-size: 0.85rem;
        padding: 0.25rem 0.5rem;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            
            {{-- WICHTIG: Das Formular umschließt die gesamte Logik --}}
            <form id="bulkActionForm" method="POST">
                @csrf
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Benachrichtigungs-Archiv</h3>
                        
                        <div class="card-tools">
                            {{-- Dropdown für Bulk-Aktionen --}}
                            <div class="btn-group mr-2">
                                <select id="bulk-action-select" class="form-control form-control-sm" style="width: auto;">
                                    <option value="">Bulk-Aktion...</option>
                                    <option value="mark_read">Auswahl als gelesen markieren</option>
                                    <option value="destroy">Auswahl löschen</option>
                                </select>
                                <button type="submit" id="bulk-action-submit" class="btn btn-sm btn-primary" disabled>Ausführen</button>
                            </div>

                            {{-- Original-Button "Alle als gelesen markieren" --}}
                            @if($unreadCount > 0)
                            <form action="{{ route('notifications.markAllRead') }}" method="POST" class="d-inline mr-2">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success" title="Alle ungelesenen als gelesen markieren">
                                    <i class="fas fa-check-double"></i> ({{ $unreadCount }})
                                </button>
                            </form>
                            @endif

                            {{-- NEU: Alle gelesenen löschen --}}
                            <form action="{{ route('notifications.clearRead') }}" method="POST" class="d-inline" onsubmit="return confirm('Möchten Sie wirklich ALLE gelesenen Benachrichtigungen löschen?');">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-danger" title="Alle gelesenen löschen">
                                    <i class="fas fa-trash-alt"></i> Alle Gelesenen löschen
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="card-body">
                        {{-- Hinweis:
                            - session('success') wird hier hinzugefügt, um Feedback nach der Aktion zu geben
                            - Dies setzt voraus, dass du in deinem layouts.app eine Sektion für 'session('success')' hast
                        --}}
                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible">
                                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                                {{ session('success') }}
                            </div>
                        @endif

                        <table id="notificationsTable" class="table table-bordered table-striped dt-responsive nowrap" style="width:100%">
                            <thead>
                                <tr>
                                    {{-- NEU: Checkbox für "Alle auswählen" --}}
                                    <th class="no-sort no-search" style="width: 10px;">
                                        <input type="checkbox" id="select-all">
                                    </th>
                                    <th class="no-sort no-search" style="width: 20px;"></th> {{-- Icon --}}
                                    <th style="width: 100px;">Status</th>
                                    <th>Benachrichtigung</th>
                                    <th style="width: 170px;">Zeitpunkt</th>
                                    <th class="no-sort no-search" style="width: 50px;">Aktion</th>
                                </tr>
                            </thead>
                            {{-- NEU: TFOOT FÜR FILTER HINZUFÜGEN --}}
                            <thead>
                                <tr>
                                    <th></th> {{-- Checkbox --}}
                                    <th></th> {{-- Icon --}}
                                    <th>Status</th>
                                    <th>Benachrichtigung</th>
                                    <th>Zeitpunkt</th>
                                    <th></th> {{-- Aktion --}}
                                </tr>
                        </thead>
                            <tbody>
                                @forelse ($allNotifications as $notification)
                                    <tr class="{{ $notification->read_at ? '' : 'font-weight-bold' }}">
                                        {{-- NEU: Checkbox für einzelne Zeile --}}
                                        <td class="text-center">
                                            <input type="checkbox" class="row-checkbox" name="notification_ids[]" value="{{ $notification->id }}">
                                        </td>
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
                                        <td colspan="6" class="text-center text-muted">
                                            Keine Benachrichtigungen vorhanden.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </form> {{-- Ende des Formulars --}}
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
      // DataTables-Instanz in einer Variable speichern
      var notificationsTable = $("#notificationsTable").DataTable({
        "language": {
            "url": "{{ asset('js/i18n/de-DE.json') }}" 
        },
        // Nach Spalte 4 (Zeitpunkt) absteigend sortieren (da Spalte 0 jetzt Checkbox ist)
        "order": [[4, 'desc']] , 
        "responsive": {
            details: {
                display: DataTable.Responsive.display.modal({
                    header: function (row) {
                        var data = row.data();
                        // data[3] ist der Benachrichtigungstext
                        return 'Details für: ' + $(data[3]).text(); 
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
        },// ======================================================
        // NEU: INITCOMPLETE FÜR SPALTENFILTER
        // ======================================================
        initComplete: function () {
            this.api().columns().every(function (colIdx) {
                var column = this;
                var $footer = $(column.footer());

                // Überspringe Checkbox (0), Icon (1) und Aktion (5)
                if (colIdx === 0 || colIdx === 1 || colIdx === 5) {
                    $footer.html(''); // Inhalt leeren
                    return;
                }

                // -----------------------------
                // Filter für STATUS (Spalte 2)
                // -----------------------------
                if (colIdx === 2) { 
                    var select = $('<select class="form-control form-control-sm"><option value="">Alle Status</option></select>')
                        .appendTo($footer)
                        .on('change', function () {
                            var val = $.fn.dataTable.util.escapeRegex($(this).val());
                            // Suche exakten String (mit ^ und $)
                            column.search(val ? '^' + val + '$' : '', true, false).draw();
                        });

                    // Werte (Neu, Gelesen) aus der Spalte auslesen und Select füllen
                    column.data().unique().sort().each(function (d, j) {
                        // Extrahiere den reinen Text (ohne Badge-HTML)
                        var text = $(d).text(); 
                        if (text && !select.find("option[value='" + text + "']").length) {
                             select.append('<option value="' + text + '">' + text + '</option>');
                        }
                    });
                    return; // Nächste Spalte
                }
                
                // -----------------------------
                // Filter für TEXT (Spalte 3 & 4)
                // -----------------------------
                var title = $footer.text();
                var input = $('<input type="text" class="form-control form-control-sm" placeholder="Suche ' + title + '..." />')
                    .appendTo($footer)
                    .on('keyup change clear', function () {
                        if (column.search() !== this.value) {
                            column.search(this.value).draw();
                        }
                    });
            });
        }
        // ======================================================
        // ENDE INITCOMPLETE
        // ======================================================

      }); // Ende DataTable Initialisierung

      // ======================================================
      // NEU: JavaScript für Bulk-Aktionen
      // ======================================================

      var $table = $('#notificationsTable');
      var $selectAll = $('#select-all');
      var $rowCheckboxes = $('.row-checkbox');
      var $bulkSubmitBtn = $('#bulk-action-submit');
      var $bulkActionSelect = $('#bulk-action-select');

      // 1. "Alle auswählen" Checkbox
      // WICHTIG: .on('click', ...) funktioniert nur für die erste Seite.
      // Wir müssen den 'change'-Event nutzen und DataTables API verwenden,
      // um ALLE Zeilen zu ändern (auch auf anderen Seiten).
      $selectAll.on('change', function() {
            var isChecked = $(this).is(':checked');
            // Finde alle Checkboxen in der Tabelle (über alle Seiten hinweg) und setze ihren Status
            notificationsTable.rows().nodes().to$().find('.row-checkbox').prop('checked', isChecked);
            updateBulkSubmitButton();
      });

      // 2. Einzelne Checkboxen
      // Wir nutzen Event-Delegation am Tabellen-Body, damit es auch nach Sortierung/Seitenwechsel funktioniert
      $table.on('change', '.row-checkbox', function() {
            // Wenn eine Checkbox abgewählt wird, deaktiviere "Alle auswählen"
            if (!$(this).is(':checked')) {
                $selectAll.prop('checked', false);
            }
            // Prüfe, ob alle Checkboxen auf der *aktuellen Seite* (oder alle) gecheckt sind
            // Für Einfachheit: Wir prüfen, ob *alle* Checkboxen (überall) gecheckt sind
            var allChecked = notificationsTable.rows().nodes().to$().find('.row-checkbox:not(:checked)').length === 0;
            $selectAll.prop('checked', allChecked);
            
            updateBulkSubmitButton();
      });

      // 3. Status des Submit-Buttons aktualisieren
      function updateBulkSubmitButton() {
            // Finde alle gecheckten Checkboxen
            var checkedCount = notificationsTable.rows().nodes().to$().find('.row-checkbox:checked').length;
            // Prüfe, ob eine Aktion (nicht der Platzhalter) gewählt ist
            var actionSelected = $bulkActionSelect.val() !== "";
            
            // Aktiviere Button nur, wenn min. 1 Checkbox UND eine Aktion gewählt ist
            $bulkSubmitBtn.prop('disabled', !(checkedCount > 0 && actionSelected));
      }

      // 4. Dropdown-Änderung überwachen
      $bulkActionSelect.on('change', function() {
          updateBulkSubmitButton();
      });

      // 5. Formular-Submit abfangen
      $('#bulkActionForm').on('submit', function(e) {
          var action = $bulkActionSelect.val();

          if (action === "destroy") {
                // Sicherheitsabfrage
                if (!confirm('Möchten Sie die ausgewählten Benachrichtigungen wirklich löschen?')) {
                    e.preventDefault(); // Abbruch
                    return;
                }
                $(this).attr('action', '{{ route('notifications.bulkDestroy') }}');

          } else if (action === "mark_read") {
                $(this).attr('action', '{{ route('notifications.bulkMarkRead') }}');

          } else {
                e.preventDefault(); // Keine Aktion gewählt
          }
      });
      
      // 6. DataTables Draw-Event (nach Seitenwechsel, Sortierung, Suche)
      // Stellt sicher, dass die Checkboxen den korrekten Status haben
      notificationsTable.on('draw.dt', function() {
          // Setze "Alle auswählen" zurück, da die Auswahl evtl. nicht mehr stimmt
          // (Es sei denn, alle *jetzt sichtbaren* sollen gecheckt sein)
          // Einfachste Lösung: "Alle auswählen" zurücksetzen
          $selectAll.prop('checked', false);
          updateBulkSubmitButton();
      });

    });
</script>
@endpush