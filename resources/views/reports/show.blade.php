@extends('layouts.app')

@section('title', 'Einsatzbericht #'.$report->id)

@php
    /**
     * Diese Helferfunktion formatiert den Berichtstext für die Anzeige.
     * 1. Sie wandelt Zeilenumbrüche in HTML <br>-Tags um.
     * 2. Sie hebt alle Platzhalter wie [TEXT] mit einem Warn-Badge hervor.
     * 3. Sie schützt vor XSS-Angriffen durch das Escapen von HTML.
     */
    function formatReportContent($text) {
        $safeText = e($text); // XSS-Schutz
        $highlightedText = preg_replace('/\[(.*?)\]/', '<span class="badge bg-warning text-dark mx-1 p-1">$0</span>', $safeText);
        return nl2br($highlightedText);
    }
@endphp

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
                    @can('update', $report)
                        <a href="{{ route('reports.edit', $report) }}" class="btn btn-primary btn-flat">
                            <i class="fas fa-edit"></i> Bearbeiten
                        </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Linke Spalte: Stammdaten -->
        <div class="col-md-4">
            <div class="card card-primary card-outline">
                <div class="card-body box-profile">
                    <h3 class="profile-username text-center">{{ $report->patient_name }}</h3>
                    <p class="text-muted text-center">Patient</p>

                    <ul class="list-group list-group-unbordered mb-3">
                        <li class="list-group-item">
                            <b>Einsatzort</b> <a class="float-right">{{ $report->location }}</a>
                        </li>
                        <li class="list-group-item">
                            <b>Erstellt von</b> <a class="float-right">{{ $report->user->name }}</a>
                        </li>
                        <li class="list-group-item">
                            <b>Datum</b> <a class="float-right">{{ $report->created_at->format('d.m.Y H:i') }}</a>
                        </li>
                    </ul>
                    @if($report->attendingStaff->isNotEmpty())
                        <hr>
                        <strong><i class="fas fa-users mr-1"></i> Beteiligte Mitarbeiter</strong>
                        <p class="text-muted">
                            @foreach($report->attendingStaff as $staff)
                                <span class="badge bg-secondary">{{ $staff->name }}</span>
                            @endforeach
                        </p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Rechte Spalte: Berichtsdetails -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-file-alt mr-1"></i>
                        {{ $report->title }}
                    </h3>
                </div>
                <div class="card-body">
                    <dl>
                        <dt>Einsatzhergang</dt>
                        <dd class="callout callout-info" style="white-space: pre-line;">
                            {!! formatReportContent($report->incident_description) !!}
                        </dd>

                        <dt>Durchgeführte Maßnahmen</dt>
                        <dd class="callout callout-success" style="white-space: pre-line;">
                            {!! formatReportContent($report->actions_taken) !!}
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
@endsection
