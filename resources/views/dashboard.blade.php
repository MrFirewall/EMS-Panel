@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    {{-- Content Header --}}
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12 text-center">
                    <h1 class="m-0">Verwaltung des Los Santos Medical Center (LSMC)</h1>
                    <p class="lead">Herzlich Willkommen, {{ Auth::user()->name }}!</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Hauptinhalt --}}
    <div class="container-fluid">
        <div class="row">

            <div class="col-lg-3">

                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Persönliche Übersicht</h3>
                    </div>
                    <div class="card-body">
                        <strong>Wochenstunden:</strong>
                        <p class="text-muted">{{ $weeklyHours }}</p>
                        <hr>
                        <strong>Letzte Berichte:</strong>
                        @forelse($lastReports as $report)
                            <div class="post">
                                <a href="{{ route('reports.show', $report) }}">Bericht #{{ $report->id }}</a> 
                                <span class="text-muted float-right">{{ $report->created_at->format('d.m.Y H:i') }}</span>
                            </div>
                        @empty
                            <p class="text-muted">Noch keine Berichte erstellt.</p>
                        @endforelse
                    </div>
                </div>

                <div class="card card-outline card-info">
                    <div class="card-header">
                        <h3 class="card-title">Dienst-Status</h3>
                    </div>
                    <div class="card-body text-center">
                        <h4 id="duty-status-text">
                            @if(Auth::user()->on_duty)
                                <span class="badge bg-success">Im Dienst</span>
                            @else
                                <span class="badge bg-danger">Außer Dienst</span>
                            @endif
                        </h4>
                        <button id="toggle-duty-btn" class="btn btn-primary mt-3">
                            {{ Auth::user()->on_duty ? 'Dienst beenden' : 'Dienst antreten' }}
                        </button>
                    </div>
                </div>

            </div>

            <div class="col-lg-6">
                
                <div class="card">
                     <div class="card-header">
                        <h3 class="card-title">Schnellzugriff</h3>
                    </div>
                    <div class="card-body d-flex justify-content-around">
                        <a href="{{ route('reports.create') }}" class="btn btn-app bg-success">
                            <i class="fas fa-file-alt"></i> Neuer Einsatzbericht
                        </a>
                    </div>
                </div>

                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Ankündigungen</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            @forelse($announcements as $announcement)
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">{{ $announcement->title }}</h6>
                                        <small class="text-muted">{{ $announcement->created_at->diffForHumans() }}</small>
                                    </div>
                                    <p class="mb-1">{{ $announcement->content }}</p>
                                    <small class="text-muted">Gepostet von: {{ $announcement->user->name }}</small>
                                </div>
                            @empty
                                <div class="list-group-item">
                                    <p class="mb-0">Aktuell gibt es keine Ankündigungen.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3">

                <div class="card card-outline card-primary">
                    <div class="card-header"><h3 class="card-title">Rangverteilung</h3></div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <strong>Gesamt</strong>
                                <span class="badge bg-secondary">{{ $totalUsers ?? 0 }}</span>
                            </li>
                            @foreach($rankDistribution as $rankName => $count)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    {{ $rankName }}
                                    <span class="badge bg-primary">{{ $count }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <div class="card card-outline card-primary">
                    <div class="card-header"><h3 class="card-title">Prüfungszulassungen</h3></div>
                    <div class="card-body">
                        <p class="mb-0 text-muted">Zur Zeit keine offenen Zulassungen.</p>
                    </div>
                </div>
                
                <div class="card card-outline card-danger">
                    <div class="card-header"><h3 class="card-title">Blacklist</h3></div>
                    <div class="card-body">
                        <p class="mb-0 text-success">Keine permanenten Einträge vorhanden.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Klick-Event für den Dienst-Status Button
    $('#toggle-duty-btn').on('click', function() {
        var button = $(this);
        // Deaktiviere Button, um doppelte Klicks zu verhindern
        button.prop('disabled', true); 

        $.ajax({
            url: '{{ route("duty.toggle") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}' // CSRF-Token für Sicherheit
            },
            success: function(response) {
                if(response.success) {
                    // UI aktualisieren
                    var statusTextContainer = $('#duty-status-text');
                    
                    if(response.new_status) { // User ist jetzt im Dienst
                        statusTextContainer.html('<span class="badge bg-success">Im Dienst</span>');
                        button.text('Dienst beenden');
                    } else { // User ist jetzt außer Dienst
                        statusTextContainer.html('<span class="badge bg-danger">Außer Dienst</span>');
                        button.text('Dienst antreten');
                    }
                } else {
                    // Optional: Fehlermeldung anzeigen
                    alert('Ein Fehler ist aufgetreten.');
                }
            },
            error: function() {
                alert('Ein Serverfehler ist aufgetreten. Bitte versuche es erneut.');
            },
            complete: function() {
                // Button wieder aktivieren
                button.prop('disabled', false);
            }
        });
    });
});
</script>
@endpush