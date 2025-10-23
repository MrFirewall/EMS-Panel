@extends('layouts.app')

@section('title', 'Berechtigungen verwalten')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Alle Berechtigungen</h3>
        <div class="card-tools">
            @can('permissions.create')
                <a href="{{ route('admin.permissions.create') }}" class="btn btn-primary btn-sm btn-flat">
                    <i class="fas fa-plus"></i> Neue Berechtigung
                </a>
            @endcan
        </div>
    </div>
    <div class="card-body">
        <table id="permissionsTable" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Beschreibung</th>
                    <th>Erstellt am</th>
                    <th class="no-sort no-search" style="width: 150px;">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($permissions as $permission)
                    <tr>
                        <td>{{ $permission->name }}</td>
                        <td>{{ $permission->description ?? '-' }}</td>
                        <td>{{ $permission->created_at->format('d.m.Y H:i') }}</td>
                        <td>
                            @can('permissions.edit')
                                <a href="{{ route('admin.permissions.edit', $permission) }}" class="btn btn-warning btn-xs btn-flat">
                                    <i class="fas fa-edit"></i> Bearbeiten
                                </a>
                            @endcan

                            @can('permissions.delete')
                                <form action="{{ route('admin.permissions.destroy', $permission) }}" method="POST" class="d-inline" onsubmit="return confirm('Sind Sie sicher?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-xs btn-flat">
                                        <i class="fas fa-trash"></i> Löschen
                                    </button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center">Keine Berechtigungen gefunden.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{-- Der card-footer mit der Paginierung wird nicht mehr benötigt, da DataTables das übernimmt --}}
</div>
@endsection

@push('scripts')
<script>
    $(function () {
      $("#permissionsTable").DataTable({
        "language": {
            "url": "{{ asset('js/i18n/de-DE.json') }}"
        },
        "order": [[1, 'desc']] , // Standardmäßig nach Name absteigend sortieren
        "responsive": true,
        "autoWidth": false,
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
        }
      });
    });
</script>
@endpush
