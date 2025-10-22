@extends('layouts.app') {{-- Ersetze 'layouts.app' durch dein Admin-Layout --}}

@section('title', 'Benachrichtigungsregel bearbeiten')

@section('content')
    {{-- AdminLTE Content Header --}}
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><i class="fas fa-edit"></i> Benachrichtigungsregel bearbeiten</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.notification-rules.index') }}">Benachrichtigungsregeln</a></li>
                        <li class="breadcrumb-item active">Bearbeiten</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="content">
        <div class="container-fluid">
             {{-- Formular beginnt hier --}}
            <form action="{{ route('admin.notification-rules.update', $notificationRule) }}" method="POST">
                @csrf {{-- WICHTIG: CSRF Token --}}
                @method('PUT') {{-- WICHTIG: Methode für Update --}}
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Regeldetails bearbeiten</h3>
                    </div>
                    {{-- Include form partial, übergibt die zu bearbeitende Regel --}}
                    @include('admin.notification-rules._form', ['notificationRule' => $notificationRule])
                </div>
            </form>
             {{-- Formular endet hier --}}
        </div>
    </div>
@endsection

{{-- Select2 JS wird aus dem _form Partial gepusht --}}
