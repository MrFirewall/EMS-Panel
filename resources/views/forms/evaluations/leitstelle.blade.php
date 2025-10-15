@extends('layouts.app')

@section('title', 'Leitstellenbewertung')

@section('content')
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-headset me-2"></i> Leitstellenbewertung erstellen</h1>
    </div>

    @if($users->isEmpty())
        <div class="alert alert-warning" role="alert">
            Aktuell sind keine Mitarbeiter im System registriert, die bewertet werden können.
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('forms.evaluations.store') }}" method="POST">
                @csrf
                <input type="hidden" name="evaluation_type" value="{{ $evaluationType }}">
                
                @include('forms.evaluations.partials.criteria') 
            </form>
        </div>
    </div>
@endsection