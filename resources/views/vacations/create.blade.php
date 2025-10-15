@extends('layouts.app')

@section('title', 'Urlaubsantrag stellen')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <h1 class="m-0"><i class="fas fa-calendar-plus me-2"></i> Neuen Urlaubsantrag stellen</h1>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    
    <div class="row">
        <div class="col-lg-6">
            <div class="alert alert-info small mt-4">
                <strong>Wichtig:</strong> Dein Antrag wird nach dem Absenden geprüft und ist erst gültig, wenn er genehmigt wurde.<br>
                Rückwirkende Urlaubsanträge werden nicht genehmigt!<br>
                Urlaubsanträge bitte immer von Montag bis Sonntag beantragen (Ausnahme Teambesprechung).
            </div>
            
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Deine Urlaubsdaten</h3>
                </div>
                <div class="card-body">
                    
                    <form action="{{ route('vacations.store') }}" method="POST">
                        @csrf

                        {{-- Start Date --}}
                        <div class="form-group">
                            <label for="start_date">Startdatum des Urlaubs</label>
                            <input type="date" class="form-control @error('start_date') is-invalid @enderror" id="start_date" name="start_date" value="{{ old('start_date') }}" required>
                            @error('start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- End Date --}}
                        <div class="form-group">
                            <label for="end_date">Enddatum des Urlaubs</label>
                            <input type="date" class="form-control @error('end_date') is-invalid @enderror" id="end_date" name="end_date" value="{{ old('end_date') }}" required>
                            @error('end_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Reason --}}
                        <div class="form-group">
                            <label for="reason">Grund / Anmerkung</label>
                            <textarea class="form-control @error('reason') is-invalid @enderror" id="reason" name="reason" rows="3">{{ old('reason') }}</textarea>
                            @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <button type="submit" class="btn btn-success mt-3 btn-flat">
                            <i class="fas fa-paper-plane me-2"></i> Antrag absenden
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            {{-- This space could be used to show past vacation requests for the user --}}
        </div>
    </div>
@endsection