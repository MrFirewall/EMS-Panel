@extends('layouts.app')
@section('title', 'Prüfungsverwaltung')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-tasks nav-icon"></i> Prüfungsverwaltung</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Prüfungen</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="content">
    <div class="container-fluid">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">Alle erstellten Prüfungen</h3>
                <div class="card-tools">
                    <a href="{{ route('admin.exams.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Neue Prüfung erstellen
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Titel</th>
                                <th>Zugehöriges Modul</th>
                                <th>Fragen</th>
                                <th class="text-right">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($exams as $exam)
                            <tr>
                                <td><strong>{{ $exam->title }}</strong></td>
                                <td>{{ $exam->trainingModule->name ?? 'N/A' }}</td>
                                <td>{{ $exam->questions_count }}</td>
                                <td class="text-right">
                                    <a href="{{ route('admin.exams.show', $exam) }}" class="btn btn-sm btn-outline-info" title="Details anzeigen"><i class="fas fa-eye"></i></a>
                                    <a href="{{ route('admin.exams.edit', $exam) }}" class="btn btn-sm btn-outline-warning" title="Prüfung bearbeiten"><i class="fas fa-edit"></i></a>
                                    <form action="{{ route('admin.exams.destroy', $exam) }}" method="POST" class="d-inline" onsubmit="return confirm('Sind Sie sicher, dass Sie diese Prüfung löschen möchten?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Prüfung löschen"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center p-3 text-muted">Es wurden noch keine Prüfungen erstellt.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($exams->hasPages())
            <div class="card-footer">
                {{ $exams->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
