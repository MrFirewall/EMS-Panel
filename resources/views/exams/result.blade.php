@extends('layouts.app')
@section('title', 'Prüfungsergebnis')

@section('content')
    {{-- AdminLTE Content Header --}}
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                     <h1 class="m-0">Ergebnis für: {{ $attempt->exam->title }}</h1>
                </div>
                 <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Prüfungsergebnis</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-8 offset-lg-2 col-md-10 offset-md-1">
                    @php
                        $passed = $attempt->score >= $attempt->exam->pass_mark;
                    @endphp
                    <div class="card {{ $passed ? 'card-success' : 'card-danger' }} card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas {{ $passed ? 'fa-check-circle' : 'fa-times-circle' }}"></i>
                                Prüfung {{ $passed ? 'bestanden' : 'nicht bestanden' }}
                            </h3>
                        </div>
                        <div class="card-body text-center">
                            <div class="display-4">{{ $attempt->score }}%</div>
                            <p class="lead">Erreichte Punktzahl</p>
                            
                            <div class="progress" style="height: 25px;">
                                <div class="progress-bar {{ $passed ? 'bg-success' : 'bg-danger' }}" role="progressbar" style="width: {{ $attempt->score }}%;" aria-valuenow="{{ $attempt->score }}" aria-valuemin="0" aria-valuemax="100">{{ $attempt->score }}%</div>
                            </div>
                            
                            <p class="mt-3">Mindestpunktzahl zum Bestehen: <strong>{{ $attempt->exam->pass_mark }}%</strong></p>
                            <hr>
                            <p>Prüfung abgeschlossen am: {{ $attempt->completed_at->format('d.m.Y \u\m H:i') }} Uhr</p>
                            
                            @if(!empty($attempt->flags))
                                <div class="alert alert-warning mt-3">
                                    <strong><i class="fas fa-exclamation-triangle"></i> Achtung:</strong> Während dieser Prüfung wurde {{ count($attempt->flags) }} Mal das Fenster verlassen. Dies wurde für den Prüfer protokolliert.
                                </div>
                            @endif
                            
                            <a href="{{ route('dashboard') }}" class="btn btn-primary mt-3">
                                <i class="fas fa-tachometer-alt"></i> Zurück zum Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

