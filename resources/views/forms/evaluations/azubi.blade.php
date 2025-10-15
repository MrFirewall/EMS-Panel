@extends('layouts.app')

@section('title', 'Azubibewertung')

@section('content')
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-person-badge me-2"></i> Azubibewertung</h1>
    </div>

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