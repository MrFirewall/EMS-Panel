@extends('layouts.app')

@section('title', 'Profil von ' . $user->name)

@section('content')
    <div class="row">
        {{-- Linke Spalte: Hauptprofil, Stammdaten & Metadaten --}}
        <div class="col-md-4">

            {{-- Haupt-Stammdaten mit Profilbild --}}
            <div class="card card-primary card-outline">
                <div class="card-body box-profile">
                    <div class="text-center">
                        <img class="profile-user-img img-fluid img-circle"
                             src="{{ $user->avatar }}"
                             alt="Profilbild"
                             style="width: 120px; height: 120px; object-fit: cover;">
                    </div>

                    <h3 class="profile-username text-center mt-3">{{ $user->name }}</h3>
                    <p class="text-muted text-center">
                        @forelse($user->getRoleNames() as $role)
                            <span class="badge bg-info">{{ $role }}</span>
                        @empty
                            Keine Rolle
                        @endforelse
                    </p>

                    {{-- Admin-Aktion: Profil bearbeiten --}}
                    @can('users.edit')
                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary btn-block btn-flat">
                            <i class="fas fa-pencil-alt me-1"></i> Profil bearbeiten
                        </a>
                    @endcan
                </div>
            </div>

            {{-- Stammdaten sind jetzt hier in der linken Spalte --}}
            @include('profile.partials.master-data', ['user' => $user])

            {{-- Metadaten sind jetzt hier, aber weiterhin nur für Admins sichtbar --}}
            @can('users.manage.record')
                @include('profile.partials.metadata', ['user' => $user])
            @endcan

        </div>

        {{-- Rechte Spalte: Detailinformationen in Tabs --}}
        <div class="col-md-8">
            <div class="card">
                <div class="card-header p-2">
                    <ul class="nav nav-pills">
                        <li class="nav-item"><a class="nav-link active" href="#overview" data-toggle="tab">Übersicht</a></li>
                        {{-- Der Personalakte-Tab wird nur für Admins angezeigt --}}
                        @can('users.manage.record')
                            <li class="nav-item"><a class="nav-link" href="#service-records-tab" data-toggle="tab">Personalakte</a></li>
                        @endcan
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">

                        {{-- TAB 1: Übersicht (für alle) --}}
                        <div class="tab-pane active" id="overview">
                            <div class="row">
                                <div class="col-md-6">
                                    @include('profile.partials.examinations', ['examinations' => $user->examinations])
                                    @include('profile.partials.training-modules', ['trainingModules' => $user->trainingModules])
                                </div>
                                <div class="col-md-6">
                                    @include('profile.partials.evaluations', ['evaluationCounts' => $evaluationCounts])
                                    @include('profile.partials.vacations', ['vacations' => $user->vacations])
                                </div>
                            </div>
                        </div>

                        {{-- TAB 2: Personalakte (nur für Admins) --}}
                        @can('users.manage.record')
                            <div class="tab-pane" id="service-records-tab">
                                {{-- Wir übergeben die paginierten $serviceRecords an das Partial --}}
                                @include('profile.partials.service-records', ['user' => $user, 'serviceRecords' => $serviceRecords])
                            </div>
                        @endcan

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection