@extends('layouts.app')
@section('title', 'Moduldetails: ' . $module->name)

@section('content')
    {{-- AdminLTE Content Header --}}
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><i class="fas fa-info-circle nav-icon"></i> Moduldetails: {{ $module->name }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('modules.index') }}">Module</a></li>
                        <li class="breadcrumb-item active">Details</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                {{-- Linke Spalte: Modul-Informationen --}}
                <div class="col-md-4">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">Stammdaten</h3>
                            <div class="card-tools">
                                @can('update', $module)
                                    <a href="{{ route('modules.edit', $module) }}" class="btn btn-sm btn-warning" title="Modul bearbeiten">
                                        <i class="fas fa-edit"></i> Bearbeiten
                                    </a>
                                @endcan
                            </div>
                        </div>
                        <div class="card-body">
                            <strong><i class="fas fa-book mr-1"></i> Name</strong>
                            <p class="text-muted">{{ $module->name }}</p>
                            <hr>
                            <strong><i class="fas fa-tag mr-1"></i> Kategorie</strong>
                            <p class="text-muted">{{ $module->category ?? 'Allgemein' }}</p>
                            <hr>
                            <strong><i class="far fa-file-alt mr-1"></i> Beschreibung</strong>
                            <p class="text-muted" style="white-space: pre-wrap;">{{ $module->description ?? 'Keine Beschreibung vorhanden.' }}</p>
                        </div>
                    </div>
                </div>

                {{-- Rechte Spalte: Zugewiesene Mitarbeiter --}}
                <div class="col-md-8">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-users mr-1"></i> Zugewiesene Mitarbeiter ({{ $module->users->count() }})</h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Status</th>
                                            <th>Abgeschlossen am</th>
                                            <th>Notizen</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($module->users as $user)
                                            <tr>
                                                <td>{{ $user->name }}</td>
                                                <td>
                                                    @php
                                                        $statusClass = match($user->pivot->status) {
                                                            'bestanden' => 'badge-success',
                                                            'nicht_bestanden' => 'badge-danger',
                                                            'in_ausbildung' => 'badge-warning',
                                                            default => 'badge-info',
                                                        };
                                                    @endphp
                                                    <span class="badge {{ $statusClass }}">
                                                        {{ str_replace('_', ' ', ucfirst($user->pivot->status)) }}
                                                    </span>
                                                </td>
                                                <td>{{ $user->pivot->completed_at ? \Carbon\Carbon::parse($user->pivot->completed_at)->format('d.m.Y') : '-' }}</td>
                                                <td>{{ $user->pivot->notes ?? '-' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted p-3">
                                                    Diesem Modul sind noch keine Mitarbeiter zugewiesen.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

