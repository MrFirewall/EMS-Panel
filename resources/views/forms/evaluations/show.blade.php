@extends('layouts.app')

@section('title', 'Details: ' . ucfirst($evaluation->evaluation_type) . ' Bewertung')

@section('content')
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i class="bi bi-eye me-2"></i> Detailansicht: {{ ucfirst($evaluation->evaluation_type) }}
        </h1>
        <a href="{{ route('forms.evaluations.index') }}" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Zurück zur Übersicht
        </a>
    </div>

    <div class="row g-4">
        
        {{-- Linke Spalte: Metadaten --}}
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Allgemeine Informationen</h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Typ</span>
                        <strong>{{ ucfirst($evaluation->evaluation_type) }}</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Datum</span>
                        <strong>{{ $evaluation->created_at->format('d.m.Y H:i') }}</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Betroffener</span>
                        <strong>{{ $targetName }}</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Verfasser</span>
                        <strong>{{ $evaluation->evaluator->name ?? 'Gelöschter Nutzer' }}</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Zeitraum</span>
                        <strong>{{ $evaluation->period }}</strong>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Rechte Spalte: Bewertungskriterien --}}
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Kriterien und Noten</h5>
                </div>
                <ul class="list-group list-group-flush">
                    @forelse($evaluationData as $criterion => $grade)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            {{-- Kriterium sauber formatieren (z.B. Verhalten_im_Dienst -> Verhalten im Dienst) --}}
                            <span>{{ ucfirst(str_replace('_', ' ', $criterion)) }}</span>
                            <span class="badge bg-secondary fs-6">{{ $grade }}</span>
                        </li>
                    @empty
                        <li class="list-group-item text-center text-muted">Keine Kriterien gefunden.</li>
                    @endforelse
                </ul>
            </div>

            {{-- Kommentar --}}
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Beschreibung / Kommentar</h5>
                </div>
                <div class="card-body">
                    <p class="mb-0">{{ $evaluation->description ?? 'Kein Kommentar hinterlegt.' }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection
    
