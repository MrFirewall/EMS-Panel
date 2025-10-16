@extends('layouts.app')

@section('title', 'Bürger-Management')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Bürger-Management</h1>
                </div>
                @can('citizens.create')
                <div class="col-sm-6 text-right">
                    <a href="{{ route('citizens.create') }}" class="btn btn-primary btn-flat">
                        <i class="fas fa-plus me-1"></i> Neue Akte anlegen
                    </a>
                </div>
                @endcan
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Bürgerarchiv durchsuchen</h3>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('citizens.index') }}">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Suche nach Name, Telefonnummer oder Adresse..." value="{{ request('search') }}">
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Suchen</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Geburtsdatum</th>
                            <th>Telefonnummer</th>
                            <th>Adresse</th>
                            <th class="text-right">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($citizens as $citizen)
                            <tr>
                                <td>{{ $citizen->name }}</td>
                                <td>{{ $citizen->date_of_birth ? \Carbon\Carbon::parse($citizen->date_of_birth)->format('d.m.Y') : 'N/A' }}</td>
                                <td>{{ $citizen->phone_number ?? 'N/A' }}</td>
                                <td>{{ $citizen->address ?? 'N/A' }}</td>
                                <td class="text-right">
                                    @can('citizens.edit')
                                    <a href="{{ route('citizens.edit', $citizen) }}" class="btn btn-sm btn-primary btn-flat">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @endcan
                                    @can('citizens.delete')
                                    <form action="{{ route('citizens.destroy', $citizen) }}" method="POST" class="d-inline" onsubmit="return confirm('Bist du sicher?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger btn-flat">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">Keine Bürgerakten gefunden.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer clearfix">
            {{ $citizens->links() }}
        </div>
    </div>
@endsection
