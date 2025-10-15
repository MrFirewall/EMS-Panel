@extends('layouts.app')

@section('title', 'Bewertungen Übersicht')

@section('content')
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-card-checklist me-2"></i> Bewertungen Übersicht</h1>
    </div>

    <div class="row g-4">
        
        {{-- Spalte Links: Neue Bewertung erstellen --}}
        {{-- NEU: Dieser gesamte Block wird nur angezeigt, wenn der Benutzer erstellen darf. --}}
        @can('evaluations.create')
            <div class="col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Neue Bewertung erstellen</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="{{ route('forms.evaluations.azubi') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            Azubibewertung <i class="bi bi-arrow-right"></i>
                        </a>
                        <a href="{{ route('forms.evaluations.praktikant') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            Praktikantenbewertung <i class="bi bi-arrow-right"></i>
                        </a>
                        <a href="{{ route('forms.evaluations.mitarbeiter') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            Mitarbeiterbewertung <i class="bi bi-arrow-right"></i>
                        </a>
                        <a href="{{ route('forms.evaluations.leitstelle') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            Leitstellenbewertung <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        @endcan

        {{-- Spalte Rechts: Übersicht und Auswertung (Hauptinhalt) --}}
        {{-- NEU: Die Spaltenbreite passt sich an, je nachdem, ob die Erstellen-Box links angezeigt wird. --}}
        <div class="@can('evaluations.create') col-lg-8 @else col-lg-12 @endcan">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    {{-- KORRIGIERT: Nutzt die neue Variable '$canViewAll' aus dem Controller --}}
                    <h5 class="mb-0">{{ $canViewAll ? 'System-Übersicht: Alle Bewertungen' : 'Ihre erhaltenen Bewertungen' }}</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Typ</th>
                                    <th>Betroffener</th>
                                    <th>Verfasser</th>
                                    <th>Datum</th>
                                    <th>Zusammenfassung</th>
                                    <th>Aktion</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($evaluations as $evaluation)
                                    @php
                                        // Deine bestehende PHP-Logik ist gut und bleibt unverändert.
                                        $data = is_array($evaluation->json_data) ? $evaluation->json_data : json_decode($evaluation->json_data, true);
                                        $summaryGrade = 'N/A';
                                        if (is_array($data)) {
                                            foreach ($data as $key => $grade) {
                                                if (!in_array($grade, ['Ja', 'Nein', 'Nicht feststellbar'])) {
                                                    $summaryGrade = $grade;
                                                    break;
                                                }
                                            }
                                        }
                                        $targetName = $evaluation->target_name 
                                            ?? $evaluation->user?->name 
                                            ?? 'Unbekannt (ID: ' . ($evaluation->user_id ?? 'N/A') . ')';
                                    @endphp
                                    <tr>
                                        <td><span class="badge bg-primary">{{ ucfirst($evaluation->evaluation_type) }}</span></td>
                                        <td>{{ $targetName }}</td>
                                        <td>{{ $evaluation->evaluator?->name ?? 'Gelöschter Nutzer' }}</td>
                                        <td>{{ $evaluation->created_at->format('d.m.Y') }}</td>
                                        <td>Erste Note: <strong>{{ $summaryGrade }}</strong></td>
                                        <td>
                                            {{-- HINWEIS: Diese Route ist im Admin-Bereich. Ein normaler User kann sie eventuell nicht aufrufen. --}}
                                            {{-- Der Schutz in der show()-Methode selbst ist aber korrekt. --}}
                                            <a href="{{ route('admin.forms.evaluations.show', $evaluation) }}" class="btn btn-sm btn-outline-info">Details</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">
                                            {{-- KORRIGIERT: Nutzt die neue Variable '$canViewAll' --}}
                                            @if($canViewAll)
                                                Es wurden noch keine Bewertungen im System erstellt.
                                            @else
                                                Sie haben noch keine Bewertungen erhalten.
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    {{ $evaluations->links() }}
                </div>
            </div>

            {{-- Zählerbox (Diese kann für alle sichtbar bleiben) --}}
            <div class="card shadow-sm mt-4">
                <div class="card-header">Meine Zähler (Verfasst/Erhalten)</div>
                 <div class="card-body p-0">
                     <table class="table table-sm mb-0">
                         <thead>
                             <tr><th>Kategorie</th><th>Verfasst</th><th>Erhalten</th></tr>
                         </thead>
                         <tbody>
                             @foreach(['azubi' => 'Azubi', 'praktikant' => 'Praktikant', 'mitarbeiter' => 'Mitarbeiter', 'leitstelle' => 'Leitstelle'] as $type => $label)
                                 <tr>
                                     <td>{{ $label }}</td>
                                     <td class="text-center">{{ $counts['verfasst'][$type] ?? 0 }}</td>
                                     <td class="text-center">{{ $counts['erhalten'][$type] ?? 0 }}</td>
                                 </tr>
                             @endforeach
                         </tbody>
                     </table>
                 </div>
            </div>
        </div>
    </div>
@endsection