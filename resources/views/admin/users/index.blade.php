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
                    {{-- Button zum Anlegen wird nur angezeigt, wenn der User das Recht hat --}}
                    @can('users.create')
                        <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-flat">
                            <i class="fas fa-plus"></i> Mitarbeiter anlegen
                        </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    {{-- HINWEIS: Der @if(session('success')) BLOCK WURDE ENTFERNT, DA SWEETALERT DIES NUN GLOBAL ÜBERNIMMT --}}

    {{-- AdminLTE Card für die Tabelle --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped table-sm mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Personalnr.</th>
                            <th scope="col">Mitarbeiternr.</th>
                            <th scope="col">Rang</th>
                            <th scope="col">Status</th>
                            <th scope="col">Gruppen</th>
                            <th scope="col">2. Fraktion</th>
                            <th scope="col" class="text-right">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    {{-- img-circle ist AdminLTE Klasse --}}
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
                                    {{-- Fallback for any other status --}}
                                    <span class="badge bg-dark">{{ $user->status }}</span>
                                @endif
                            </td>
                            <td>
                                @forelse($user->getRoleNames() as $role)
                                    <span class="badge bg-primary">{{ $role }}</span>
                                @empty
                                    <span class="badge bg-light text-dark">Keine</span>
                                @endforelse
                            </td>

                            <td>{{ $user->second_faction }}</td>
                            <td class="text-right">
                                @can('users.edit')
                                    {{-- Action Buttons mit Font Awesome Icons und btn-flat --}}
                                    <a href="{{ route('admin.users.show', $user) }}" class="btn btn-sm btn-default btn-flat" data-toggle="tooltip" title="Personalakte einsehen">
                                        <i class="fas fa-file-alt"></i>
                                    </a>
                                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-primary btn-flat" data-toggle="tooltip" title="Mitarbeiter bearbeiten">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                @endcan
                                @canImpersonate
                                    {{-- Zeige den Button nur an, wenn der Admin die Berechtigung hat --}}
                                    <a href="{{ route('impersonate', $user->id) }}" class="btn btn-sm btn-secondary btn-flat" title="Als {{ $user->name }} einloggen">
                                        <i class="fas fa-user-secret"></i>
                                    </a>
                                @endCanImpersonate
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted p-4">Keine Mitarbeiter im System gefunden.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
