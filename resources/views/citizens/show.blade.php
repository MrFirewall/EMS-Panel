@extends('layouts.app')
@section('title', 'Bürgerakte: ' . $citizen->name)
@section('content')
    {{-- Überschrift und Zurück-Button --}}
    <div class="row mb-3">
        <div class="col-sm-6">
            <h1>
                Bürgerakte: <strong>{{ $citizen->name }}</strong>
            </h1>
        </div>
        <div class="col-sm-6">
            <a href="{{ route('citizens.index') }}" class="btn btn-primary float-sm-right">
                <i class="fas fa-arrow-left"></i> Zurück zur Übersicht
            </a>
        </div>
    </div>
    <div class="row">
        {{-- Linke Spalte mit den Stammdaten --}}
        <div class="col-md-5">
            
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-id-card mr-1"></i> Stammdaten</h3>
                </div>
                <div class="card-body">
                    <strong><i class="fas fa-user mr-1"></i> Name</strong>
                    <p class="text-muted">{{ $citizen->name }}</p>
                    <hr>

                    <strong><i class="fas fa-birthday-cake mr-1"></i> Geburtsdatum</strong>
                    <p class="text-muted">
                        {{ $citizen->date_of_birth ? \Carbon\Carbon::parse($citizen->date_of_birth)->format('d.m.Y') : 'Nicht angegeben' }}
                    </p>
                    <hr>

                    <strong><i class="fas fa-phone mr-1"></i> Telefonnummer</strong>
                    <p class="text-muted">{{ $citizen->phone_number ?? 'Nicht angegeben' }}</p>
                    <hr>

                    <strong><i class="fas fa-map-marker-alt mr-1"></i> Adresse</strong>
                    <p class="text-muted">{{ $citizen->address ?? 'Nicht angegeben' }}</p>
                    <hr>

                    <strong><i class="far fa-file-alt mr-1"></i> Notizen</strong>
                    <p class="text-muted" style="white-space: pre-wrap;">{{ $citizen->notes ?? 'Keine Notizen vorhanden.' }}</p>
                </div>
                <div class="card-footer">
                    <a href="{{ route('citizens.edit', $citizen) }}" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Akte bearbeiten
                    </a>
                </div>
            </div>

        </div>

        {{-- Rechte Spalte mit den Berichten --}}
        <div class="col-md-7">

            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-notes-medical mr-1"></i> Krankenakte / Berichte</h3>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse ($citizen->reports->sortByDesc('created_at') as $report)
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
                                <p class="text-muted">Für diesen Bürger sind noch keine Berichte vorhanden.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

        </div>
    </div>

@endsection