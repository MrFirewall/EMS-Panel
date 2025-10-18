@extends('layouts.app')
@section('title', 'Modul bearbeiten')

@section('content')
<div class="container-fluid">
    <!-- Seitenüberschrift und Breadcrumbs -->
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>Modul bearbeiten: {{ $module->name }}</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.modules.index') }}">Module</a></li>
                <li class="breadcrumb-item active">Bearbeiten</li>
            </ol>
        </div>
    </div>

    <!-- Hauptinhalt der Seite -->
    <div class="row">
        <div class="col-md-12">
            <form action="{{ route('admin.modules.update', $module) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card card-warning">
                    <div class="card-header">
                        <h3 class="card-title">Moduldetails</h3>
                    </div>
                    <div class="card-body">
                        @include('admin.training_modules._form')
                    </div>
                    <div class="card-footer">
                        <a href="{{ route('admin.modules.index') }}" class="btn btn-secondary">Abbrechen</a>
                        <button type="submit" class="btn btn-primary float-right">Änderungen speichern</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

