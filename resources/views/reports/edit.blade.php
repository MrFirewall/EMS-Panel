@extends('layouts.app')

@section('title', 'Einsatzbericht bearbeiten')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <h1 class="m-0">Einsatzbericht #{{ $report->id }} bearbeiten</h1>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-outline card-primary">
        <div class="card-body">
            <form method="POST" action="{{ route('reports.update', $report) }}">
                @csrf
                @method('PUT') {{-- Wichtig für die Update-Route --}}

                <div class="form-group">
                    <label for="title">Titel / Einsatzstichwort</label>
                    <input type="text" class="form-control" id="title" name="title" value="{{ old('title', $report->title) }}" required>
                </div>

                <div class="form-group">
                    <label for="patient_name">Name des Patienten</label>
                    <input type="text" class="form-control" id="patient_name" name="patient_name" value="{{ old('patient_name', $report->patient_name) }}" required>
                </div>

                <div class="form-group">
                    <label for="location">Einsatzort</label>
                    <input type="text" class="form-control" id="location" name="location" value="{{ old('location', $report->location) }}" required>
                </div>

                <div class="form-group">
                    <label for="incident_description">Einsatzhergang</label>
                    <textarea class="form-control" id="incident_description" name="incident_description" rows="5" required>{{ old('incident_description', $report->incident_description) }}</textarea>
                </div>

                <div class="form-group">
                    <label for="actions_taken">Durchgeführte Maßnahmen</label>
                    <textarea class="form-control" id="actions_taken" name="actions_taken" rows="5" required>{{ old('actions_taken', $report->actions_taken) }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-flat">
                    <i class="fas fa-save me-1"></i> Änderungen speichern
                </button>
                <a href="{{ route('reports.index') }}" class="btn btn-default btn-flat">Abbrechen</a>
            </form>
        </div>
    </div>
@endsection
