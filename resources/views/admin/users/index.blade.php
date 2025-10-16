@extends('layouts.app')

@section('title', 'Mitarbeiter verwalten')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Mitarbeiter verwalten</h1>
                </div>
                <div class="col-sm-6 text-right">
                    @can('users.create')
                        <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-flat">
                            <i class="fas fa-plus"></i> Mitarbeiter anlegen
                        </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        {{-- KORREKTUR: card-body hat jetzt ein normales Padding --}}
        <div class="card-body">
            {{-- HINWEIS: table-responsive wird von DataTables nicht mehr benötigt --}}
            <table id="usersTable" class="table table-hover nowrap">
                <thead>
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Personalnr.</th>
                        <th scope="col">Mitarbeiternr.</th>
                        <th scope="col">Rang</th>
                        <th scope="col">Status</th>
                        <th scope="col">Gruppen</th>
                        <th scope="col">2. Fraktion</th>
                        {{-- Die Spalte für Aktionen ist nicht durchsuchbar/sortierbar --}}
                        <th scope="col" class="text-right no-sort no-search">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="{{ $user->avatar ?? 'https://placehold.co/32x32/6c757d/FFFFFF?text=' . substr($user->name, 0, 1) }}" alt="{{ $user->name }}" width="32" height="32" class="img-circle me-2 elevation-1">
                                <span>{{ $user->name }}</span>
                            </div>
                        </td>
                        <td>{{ $user->personal_number ?? '-' }}</td>        
                        <td>{{ $user->employee_id ?? '-' }}</td>
                        <td>{{ $user->rank }}</td>
                        <td>
                            @if($user->status == 'Aktiv')
                                <span class="badge bg-success">Aktiv</span>
                            @elseif($user->status == 'Probezeit')
                                <span class="badge bg-info">Probezeit</span>
                            @elseif($user->status == 'Beobachtung')
                                <span class="badge bg-info">Beobachtung</span>
                            @elseif($user->status == 'Beurlaubt')
                                <span class="badge bg-warning">Beurlaubt</span>
                            @elseif($user->status == 'Krankgeschrieben')
                                <span class="badge bg-warning">Krankgeschrieben</span>
                            @elseif($user->status == 'Suspendiert')
                                <span class="badge bg-danger">Suspendiert</span>
                            @elseif($user->status == 'Ausgetreten')
                                <span class="badge bg-secondary">Ausgetreten</span>
                            @elseif($user->status == 'Bewerbungsphase')
                                <span class="badge bg-light text-dark">Bewerbungsphase</span>
                            @else
                                <span class="badge bg-dark">{{ $user->status }}</span>
                            @endif
                        </td>
                        <td>
                            {{-- Verwendung von getRoleNames() aus dem User-Modell --}}
                            @forelse($user->getRoleNames() as $role)
                                <span class="badge bg-primary">{{ $role }}</span>
                            @empty
                                <span class="badge bg-light text-dark">Keine</span>
                            @endforelse
                        </td>
                        <td>{{ $user->second_faction }}</td>
                        <td class="text-right">
                            @can('users.edit')
                                <a href="{{ route('admin.users.show', $user) }}" class="btn btn-sm btn-default btn-flat" data-toggle="tooltip" title="Personalakte einsehen">
                                    <i class="fas fa-file-alt"></i>
                                </a>
                                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-primary btn-flat" data-toggle="tooltip" title="Mitarbeiter bearbeiten">
                                    <i class="fas fa-edit"></i>
                                </a>
                            @endcan
                            @canImpersonate
                                @if(auth()->user()->canBeImpersonated($user))
                                    <a href="{{ route('impersonate', $user->id) }}" class="btn btn-sm btn-secondary btn-flat" title="Als {{ $user->name }} einloggen">
                                        <i class="fas fa-user-secret"></i>
                                    </a>
                                @endif
                            @endCanImpersonate
                        </td>
                    </tr>
                    @empty
                    {{-- Dieser Teil wird bei DataTables serverseitig leer gelassen --}}
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $(function () {
      $("#usersTable").DataTable({
        // Deutsche Sprachoptionen laden
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/German.json"
        },
        "order": [[1, 'desc']] , // Standardmäßig nach Personalnummer absteigend sortieren
        "responsive": true, 
        "autoWidth": true,
        "paging": true,
        "ordering": true,
        "info": true,        
        // WICHTIG: Suche aktivieren
        "searching": true,         
        // "Zeige X Einträge" deaktivieren (optional, nach Wunsch)
        "lengthChange": true,
        "lengthMenu": [10, 25, 50, -1],
        // Spezifische Spalten vom Sortieren/Suchen ausschließen
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
