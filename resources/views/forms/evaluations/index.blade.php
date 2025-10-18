@extends('layouts.app')
@section('title', 'Formular-Übersicht')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <h1 class="m-0"><i class="fas fa-file-alt nav-icon"></i> Formular- & Antragsübersicht</h1>
    </div>
</div>

<div class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                {{-- NEU: Eigener Abschnitt für offene Anträge --}}
                @php
                    $antraege = $evaluations->filter(function($item) {
                        return in_array($item->evaluation_type, ['modul_anmeldung', 'pruefung_anmeldung']);
                    });
                @endphp

                @if($antraege->isNotEmpty() && $canViewAll)
                <div class="card card-warning card-outline">
                    <div class="card-header"><h3 class="card-title">Offene Anträge</h3></div>
                    <div class="card-body p-0">
                        <table class="table table-hover">
                            <thead><tr><th>Typ</th><th>Antragsteller</th><th>Modul</th><th>Datum</th><th>Aktion</th></tr></thead>
                            <tbody>
                                @foreach($antraege as $antrag)
                                <tr>
                                    <td><span class="badge bg-warning">{{ ucfirst(str_replace('_', ' ', $antrag->evaluation_type)) }}</span></td>
                                    <td>{{ $antrag->target_name }}</td>
                                    <td>{{ $antrag->json_data['module_name'] ?? 'N/A' }}</td>
                                    <td>{{ $antrag->created_at->format('d.m.Y') }}</td>
                                    <td>
                                        @if($antrag->evaluation_type === 'modul_anmeldung')
                                            {{-- NEU: Button für Ausbilder --}}
                                            <form action="{{ route('admin.training.assign', ['user' => $antrag->user_id, 'module' => $antrag->json_data['module_id']]) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success">Ausbildung starten</button>
                                            </form>
                                        @else
                                            <a href="#" class="btn btn-sm btn-info">Prüfung ansetzen</a>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                {{-- Bestehender Abschnitt für Bewertungen --}}
                <div class="card card-primary card-outline">
                    <div class="card-header"><h3 class="card-title">Eingereichte Bewertungen</h3></div>
                    <div class="card-body p-0">
                        <table class="table table-striped table-hover">
                             <thead><tr><th>Typ</th><th>Betroffener</th><th>Verfasser</th><th>Datum</th><th>Aktion</th></tr></thead>
                             <tbody>
                                @forelse($evaluations->whereNotIn('evaluation_type', ['modul_anmeldung', 'pruefung_anmeldung']) as $evaluation)
                                    <tr>
                                        <td><span class="badge bg-primary">{{ ucfirst($evaluation->evaluation_type) }}</span></td>
                                        <td>{{ $evaluation->target_name ?? $evaluation->user?->name ?? 'N/A' }}</td>
                                        <td>{{ $evaluation->evaluator?->name ?? 'N/A' }}</td>
                                        <td>{{ $evaluation->created_at->format('d.m.Y') }}</td>
                                        <td><a href="{{ route('admin.forms.evaluations.show', $evaluation) }}" class="btn btn-sm btn-outline-info">Details</a></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted p-3">Keine Bewertungen gefunden.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection