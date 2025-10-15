@extends('layouts.app')

@section('title', 'Einsatzberichte')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Einsatzberichte</h1>
                </div>
                <div class="col-sm-6 text-right">
                    {{-- NEU: Prüft die 'create'-Methode der Policy --}}
                    @can('create', App\Models\Report::class)
                        <a href="{{ route('reports.create') }}" class="btn btn-primary btn-flat">
                            <i class="fas fa-plus me-1"></i> Neuen Bericht erstellen
                        </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    {{-- ... (deine success-message) ... --}}

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    {{-- ... (Tabellenkopf bleibt gleich) ... --}}
                    <tbody>
                        @forelse($reports as $report)
                            <tr>
                                <td>{{ $report->created_at->format('d.m.Y H:i') }}</td>
                                <td>{{ $report->title }}</td>
                                <td>{{ $report->patient_name }}</td>
                                <td>{{ $report->user->name }}</td>
                                <td class="text-right">
                                    {{-- NEU: Prüft die 'view'-Methode der Policy --}}
                                    @can('view', $report)
                                        <a href="{{ route('reports.show', $report) }}" class="btn btn-sm btn-default btn-flat">
                                            <i class="fas fa-eye"></i> Ansehen
                                        </a>
                                    @endcan
                                    
                                    {{-- NEU: Prüft die 'update'-Methode der Policy (berechnet Ownership vs Admin) --}}
                                    @can('update', $report)
                                        <a href="{{ route('reports.edit', $report) }}" class="btn btn-sm btn-primary btn-flat">
                                            <i class="fas fa-edit"></i> Bearbeiten
                                        </a>
                                    @endcan

                                    {{-- NEU: Prüft die 'delete'-Methode der Policy --}}
                                    @can('delete', $report)
                                        <form action="{{ route('reports.destroy', $report) }}" method="POST" class="d-inline" onsubmit="return confirm('Bist du sicher?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger btn-flat">
                                                <i class="fas fa-trash"></i> Löschen
                                            </button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">Noch keine Berichte vorhanden.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer clearfix">
            {{ $reports->links() }}
        </div>
    </div>
@endsection