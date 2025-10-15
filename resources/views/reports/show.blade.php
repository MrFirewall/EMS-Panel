@extends('layouts.app')

@section('title', 'Einsatzbericht #'.$report->id)

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Einsatzbericht #{{ $report->id }}</h1>
                </div>
                <div class="col-sm-6 text-right">
                    <a href="{{ route('reports.index') }}" class="btn btn-default btn-flat me-2">
                        <i class="fas fa-arrow-left"></i> Zurück zur Übersicht
                    </a>
                    <a href="{{ route('reports.edit', $report) }}" class="btn btn-primary btn-flat">
                        <i class="fas fa-edit"></i> Bearbeiten
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-0">
            <h3 class="card-title">{{ $report->title }}</h3>
            <div class="card-tools">
                <small class="text-muted">Erstellt am {{ $report->created_at->format('d.m.Y H:i') }} Uhr</small>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <h6 class="text-muted">Patient</h6>
                    <p class="text-lg">{{ $report->patient_name }}</p>
                </div>
                <div class="col-md-6 mb-3">
                    <h6 class="text-muted">Einsatzort</h6>
                    <p class="text-lg">{{ $report->location }}</p>
                </div>
            </div>

            <hr>

            <h5 class="text-muted">Einsatzhergang</h5>
            <p class="callout callout-info">{{ $report->incident_description }}</p>

            <h5 class="text-muted">Durchgeführte Maßnahmen</h5>
            <p class="callout callout-success">{{ $report->actions_taken }}</p>
        </div>
        <div class="card-footer">
            Erstellt von <strong>{{ $report->user->name }}</strong>
        </div>
    </div>
@endsection
