@extends('layouts.app')

@section('title', 'Ausbildungsmodule verwalten')

@section('content')
    {{-- Seiten-Header, angepasst an dein Vorbild --}}
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><i class="fas fa-graduation-cap nav-icon"></i> Ausbildungsmodule verwalten</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Ausbildungsmodule</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    {{-- Hauptinhalt --}}
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                
                {{-- Spalte Links: Aktionen --}}
                @can('create', \App\Models\TrainingModule::class)
                    <div class="col-lg-4">
                        <div class="card card-primary card-outline h-100">
                            <div class="card-header">
                                <h5 class="mb-0">Modul-Aktionen</h5>
                            </div>
                            <div class="card-body">
                                <p class="card-text">
                                    Erstellen Sie neue Module, um die Qualifikationen Ihrer Mitarbeiter zu definieren und zu verfolgen.
                                </p>
                                <a href="{{ route('admin.modules.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Neues Modul erstellen
                                </a>
                            </div>
                        </div>
                    </div>
                @endcan

                {{-- Spalte Rechts: Modul-Tabelle --}}
                {{-- Die Spaltenbreite passt sich an, je nachdem, ob die Aktionen-Box angezeigt wird --}}
                <div class="@can('create', \App\Models\TrainingModule::class) col-lg-8 @else col-lg-12 @endcan">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">Alle Ausbildungsmodule</h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 30%;">Name</th>
                                            <th style="width: 20%;">Kategorie</th>
                                            <th style="width: 30%;">Beschreibung</th>
                                            <th style="width: 20%;" class="text-right">Aktionen</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($modules as $module)
                                            <tr>
                                                <td><strong>{{ $module->name }}</strong></td>
                                                <td>
                                                    <span class="badge bg-secondary">{{ $module->category ?? 'Allgemein' }}</span>
                                                </td>
                                                <td>{{ Str::limit($module->description, 50) }}</td>
                                                <td class="text-right">
                                                    {{-- Die "show" Route wurde im Controller definiert und kann hier genutzt werden --}}
                                                    <a href="{{ route('admin.modules.show', $module) }}" class="btn btn-sm btn-outline-info" title="Details anzeigen">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    
                                                    @can('update', $module)
                                                        <a href="{{ route('admin.modules.edit', $module) }}" class="btn btn-sm btn-outline-warning" title="Modul bearbeiten">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    @endcan
                                                    
                                                    @can('delete', $module)
                                                        <form action="{{ route('admin.modules.destroy', $module) }}" method="POST" class="d-inline" onsubmit="return confirm('Sind Sie sicher, dass Sie dieses Modul unwiderruflich löschen möchten? Alle Zuweisungen gehen verloren!');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Modul löschen">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    @endcan
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">
                                                    Es wurden noch keine Ausbildungsmodule erstellt.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @if ($modules->hasPages())
                            <div class="card-footer">
                                {{ $modules->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

