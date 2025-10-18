@extends('layouts.app')
@section('title', 'Ausbildungsmodule')

@section('content')
<div class="container-fluid">
    <!-- Seitenüberschrift und Breadcrumbs -->
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>Verwaltung der Ausbildungsmodule</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Ausbildungsmodule</li>
            </ol>
        </div>
    </div>

    <!-- Hauptinhalt der Seite -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Alle verfügbaren Module</h3>
            <div class="card-tools">
                @can('training.create')
                    <a href="{{ route('admin.modules.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Neues Modul erstellen
                    </a>
                @endcan
            </div>
        </div>
        <!-- /.card-header -->
        <div class="card-body p-0">
            @if($modules->isEmpty())
                <div class="alert alert-info m-3">
                    Es wurden noch keine Ausbildungsmodule erstellt.
                </div>
            @else
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th style="width: 10px">#</th>
                            <th>Name</th>
                            <th>Kategorie</th>
                            <th>Beschreibung</th>
                            <th style="width: 150px">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($modules as $module)
                        <tr>
                            <td>{{ $module->id }}</td>
                            <td><strong>{{ $module->name }}</strong></td>
                            <td>{{ $module->category ?? 'Allgemein' }}</td>
                            <td>{{ Str::limit($module->description, 70) }}</td>
                            <td>
                                <form action="{{ route('admin.modules.destroy', $module) }}" method="POST">
                                    @can('training.edit')
                                        <a href="{{ route('admin.modules.edit', $module) }}" class="btn btn-warning btn-xs"><i class="fas fa-edit"></i></a>
                                    @endcan
                                    @can('training.delete')
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-xs" onclick="return confirm('Sind Sie sicher, dass Sie dieses Modul löschen möchten?')"><i class="fas fa-trash"></i></button>
                                    @endcan
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
        <!-- /.card-body -->
        @if($modules->hasPages())
            <div class="card-footer clearfix">
                {{ $modules->links() }}
            </div>
        @endif
    </div>
    <!-- /.card -->
</div>
@endsection

 