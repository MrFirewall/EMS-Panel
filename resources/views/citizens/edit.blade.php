@extends('layouts.app')

@section('title', 'Bürgerakte bearbeiten')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <h1 class="m-0">Bürgerakte bearbeiten: {{ $citizen->name }}</h1>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card card-outline card-primary">
        <div class="card-body">
            <form method="POST" action="{{ route('citizens.update', $citizen) }}">
                @csrf
                @method('PUT')
                @include('citizens._form')
                <button type="submit" class="btn btn-primary btn-flat">
                    <i class="fas fa-save me-1"></i> Änderungen speichern
                </button>
                <a href="{{ route('citizens.index') }}" class="btn btn-default btn-flat">Abbrechen</a>
            </form>
        </div>
    </div>
@endsection
