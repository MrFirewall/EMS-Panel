@extends('layouts.app')
@section('title', 'Patientenakte: ' . $citizen->name)
@section('content')
    {{-- Überschrift und Buttons --}}
    <div class="row mb-3">
        <div class="col-sm-6">
            <h1>
                <i class="fas fa-file-medical-alt"></i> Patientenakte: <strong>{{ $citizen->name }}</strong>
            </h1>
        </div>
        <div class="col-sm-6 text-right">
            <a href="{{ route('citizens.edit', $citizen) }}" class="btn btn-warning">
                <i class="fas fa-edit"></i> Akte bearbeiten
            </a>
            <a href="{{ route('citizens.index') }}" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Zurück zur Übersicht
            </a>
        </div>
    </div>

    {{-- Tab-Navigation --}}
    <div class="card card-primary card-outline card-outline-tabs">
        <div class="card-header p-0 border-bottom-0">
            <ul class="nav nav-tabs" id="custom-tabs-four-tab" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="tab-master-data-tab" data-toggle="pill" href="#tab-master-data" role="tab" aria-controls="tab-master-data" aria-selected="true">
                        <i class="fas fa-id-card"></i> Stammdaten
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="tab-history-tab" data-toggle="pill" href="#tab-history" role="tab" aria-controls="tab-history" aria-selected="false">
                        <i class="fas fa-history"></i> Historie / Berichte
                    </a>
                </li>
                {{-- Platzhalter für zukünftige Module gemäß deiner Roadmap --}}
                <li class="nav-item">
                    <a class="nav-link" id="tab-prescriptions-tab" data-toggle="pill" href="#tab-prescriptions" role="tab" aria-controls="tab-prescriptions" aria-selected="false">
                        <i class="fas fa-pills"></i> Rezepte
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="tab-surgeries-tab" data-toggle="pill" href="#tab-surgeries" role="tab" aria-controls="tab-surgeries" aria-selected="false">
                        <i class="fas fa-briefcase-medical"></i> OPs & Verletzungen
                    </a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="custom-tabs-four-tabContent">
                {{-- TAB 1: STAMMDATEN --}}
                <div class="tab-pane fade show active" id="tab-master-data" role="tabpanel" aria-labelledby="tab-master-data-tab">
                    <div class="row">
                        {{-- Persönliche Daten --}}
                        <div class="col-md-6">
                            <h4>Persönliche Daten</h4>
                            <dl class="dl-horizontal">
                                <dt><i class="fas fa-user mr-1"></i> Name</dt>
                                <dd>{{ $citizen->name }}</dd>
                                <dt><i class="fas fa-birthday-cake mr-1"></i> Geburtsdatum</dt>
                                <dd>{{ $citizen->date_of_birth ? \Carbon\Carbon::parse($citizen->date_of_birth)->format('d.m.Y') : 'Nicht angegeben' }}</dd>
                                <dt><i class="fas fa-phone mr-1"></i> Telefonnummer</dt>
                                <dd>{{ $citizen->phone_number ?? 'Nicht angegeben' }}</dd>
                                <dt><i class="fas fa-map-marker-alt mr-1"></i> Adresse</dt>
                                <dd>{{ $citizen->address ?? 'Nicht angegeben' }}</dd>
                            </dl>
                        </div>
                        {{-- Medizinische Daten --}}
                        <div class="col-md-6">
                            <h4>Medizinische Daten</h4>
                             <dl class="dl-horizontal">
                                <dt><i class="fas fa-tint mr-1"></i> Blutgruppe</dt>
                                <dd>{{ $citizen->blood_type ?? 'Nicht angegeben' }}</dd>
                                <dt><i class="fas fa-exclamation-triangle mr-1"></i> Allergien</dt>
                                <dd style="white-space: pre-wrap;">{{ $citizen->allergies ?? 'Keine bekannt' }}</dd>
                                <dt><i class="fas fa-notes-medical mr-1"></i> Vorerkrankungen</dt>
                                <dd style="white-space: pre-wrap;">{{ $citizen->preexisting_conditions ?? 'Keine bekannt' }}</dd>
                                <dt><i class="fas fa-user-shield mr-1"></i> Notfallkontakt</dt>
                                <dd>{{ $citizen->emergency_contact_name ?? 'Nicht angegeben' }} ({{ $citizen->emergency_contact_phone ?? 'N/A' }})</dd>
                            </dl>
                        </div>
                    </div>
                     <hr>
                    <h4><i class="far fa-file-alt mr-1"></i> Allgemeine Notizen</h4>
                    <p class="text-muted" style="white-space: pre-wrap;">{{ $citizen->notes ?? 'Keine Notizen vorhanden.' }}</p>
                </div>

                {{-- TAB 2: HISTORIE / BERICHTE --}}
                <div class="tab-pane fade" id="tab-history" role="tabpanel" aria-labelledby="tab-history-tab">
                     <div class="list-group list-group-flush">
                        @forelse ($citizen->reports as $report)
                            <a href="{{ route('reports.show', $report) }}" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1"><strong>{{ $report->title }}</strong></h5>
                                    <small class="text-muted">{{ $report->created_at->format('d.m.Y H:i') }} Uhr</small>
                                </div>
                                <p class="mb-1">
                                    <strong>Vorgefallenes:</strong> {{ Str::limit($report->incident_description, 150) }}
                                </p>
                                <small class="text-muted">Erstellt von: {{ $report->user->name ?? 'Unbekannt' }}</small>
                            </a>
                        @empty
                            <div class="list-group-item">
                                <p class="text-center text-muted">Für diesen Patienten sind noch keine Berichte vorhanden.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                 {{-- TAB 3: REZEPTE (Platzhalter) --}}
                <div class="tab-pane fade" id="tab-prescriptions" role="tabpanel" aria-labelledby="tab-prescriptions-tab">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title">Ausgestellte Rezepte</h5>
                        {{-- Button, um ein neues Rezept zu erstellen --}}
                        <a href="{{ route('prescriptions.create', $citizen) }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Neues Rezept
                        </a>
                    </div>

                    @if($citizen->prescriptions->isEmpty())
                        <p class="text-center text-muted">Für diesen Patienten sind noch keine Rezepte vorhanden.</p>
                    @else
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Ausgestellt am</th>
                                    <th>Medikament</th>
                                    <th>Dosierung</th>
                                    <th>Hinweise</th>
                                    <th>Ausgestellt von</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($citizen->prescriptions as $prescription)
                                    <tr>
                                        <td>{{ $prescription->created_at->format('d.m.Y') }}</td>
                                        <td><strong>{{ $prescription->medication }}</strong></td>
                                        <td>{{ $prescription->dosage }}</td>
                                        <td>{{ $prescription->notes ?? '-' }}</td>
                                        <td>{{ $prescription->user->name ?? 'Unbekannt' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
                
                 {{-- TAB 4: OPERATIONEN (Platzhalter) --}}
                <div class="tab-pane fade" id="tab-surgeries" role="tabpanel" aria-labelledby="tab-surgeries-tab">
                    <p class="text-center text-muted">Hier werden zukünftig alle Operationsberichte und Verletzungen dokumentiert.</p>
                    {{-- Hier kommt die Logik für OPs etc. rein --}}
                </div>
            </div>
        </div>
    </div>
@endsection