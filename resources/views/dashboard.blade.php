@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    {{-- AdminLTE Content Header (Optional) --}}
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12 text-center">
                    <h1 class="m-0">Verwaltung des Los Santos Medical Center (LSMC)</h1>
                    <p class="lead">Herzlich Willkommen {{ Auth::user()->name }}!</p>
                </div>
            </div>
        </div>
    </div>
    {{-- /.content-header --}}

    <div class="row">
        <!-- Spalte Links: Prüfungszulassungen (col-lg-3) -->
        <div class="col-lg-3 col-md-4">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Prüfungszulassungen</h3>
                </div>
                <div class="card-body">
                    <p class="mb-0 text-muted">Zur Zeit keine offenen Zulassungen.</p>
                </div>
            </div>
        </div>

        <!-- Spalte Mitte: Neuigkeiten & Blacklist (col-lg-6) -->
        <div class="col-lg-6 col-md-8">
            <div class="card card-outline card-primary mb-4">
                <div class="card-header">
                    <h3 class="card-title">Neuigkeiten</h3>
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
                                <p class="mb-0">Aktuell gibt es keine Neuigkeiten.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="card card-outline card-danger">
                <div class="card-header">
                    <h3 class="card-title">Blacklist</h3>
                </div>
                <div class="card-body">
                    <p class="mb-0 text-success">Aktuell sind keine permanenten Einträge vorhanden.</p>
                </div>
            </div>
        </div>

        <!-- Spalte Rechts: Rangverteilung (col-lg-3) -->
        <div class="col-lg-3 col-md-4">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Rangverteilung</h3>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        {{-- Gesamtanzahl --}}
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <strong>gesamt</strong>
                            <span class="badge bg-secondary">{{ $totalUsers ?? 0 }}</span>
                        </li>

                        {{-- Dynamische Rangverteilung --}}
                        @forelse($rankDistribution ?? [] as $rankName => $count)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                {{ $rankName }}
                                <span class="badge bg-primary">{{ $count }}</span>
                            </li>
                        @empty
                            <li class="list-group-item">Keine Ränge gefunden.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
