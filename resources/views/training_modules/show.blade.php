@extends('layouts.app')
@section('title', 'Moduldetails: ' . $module->name)

@section('content')
<div class="container-fluid">
    <!-- Seitenüberschrift und Breadcrumbs -->
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>Moduldetails: {{ $module->name }}</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.modules.index') }}">Module</a></li>
                <li class="breadcrumb-item active">Details</li>
            </ol>
        </div>
    </div>

    <!-- Hauptinhalt der Seite -->
    <div class="row">
        <div class="col-md-4">
            <div class="card card-primary card-outline">
                <div class="card-body">
                    <strong><i class="fas fa-book mr-1"></i> Name</strong>
                    <p class="text-muted">{{ $module->name }}</p>
                    <hr>
                    <strong><i class="fas fa-tag mr-1"></i> Kategorie</strong>
                    <p class="text-muted">{{ $module->category ?? 'Allgemein' }}</p>
                    <hr>
                    <strong><i class="far fa-file-alt mr-1"></i> Beschreibung</strong>
                    <p class="text-muted">{{ $module->description ?? 'Keine Beschreibung vorhanden.' }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-users mr-1"></i> Zugewiesene Mitarbeiter</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Status</th>
                                <th>Abgeschlossen am</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($module->users as $user)
                                <tr>
                                    <td>{{ $user->name }}</td>
                                    <td>
                                        <span class="badge {{ 
                                            $user->pivot->status == 'bestanden' ? 'badge-success' : 
                                            ($user->pivot->status == 'nicht_bestanden' ? 'badge-danger' : 'badge-info') 
                                        }}">
                                            {{ str_replace('_', ' ', ucfirst($user->pivot->status)) }}
                                        </span>
                                    </td>
                                    <td>{{ $user->pivot->completed_at ? \Carbon\Carbon::parse($user->pivot->completed_at)->format('d.m.Y') : '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center">Diesem Modul sind noch keine Mitarbeiter zugewiesen.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

