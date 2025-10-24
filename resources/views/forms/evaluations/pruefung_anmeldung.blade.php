@extends('layouts.app')
@section('title', 'Formular: Prüfungs-Anmeldung')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Prüfungs-Anmeldung</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('forms.evaluations.index') }}">Formulare</a></li>
                    <li class="breadcrumb-item active">Prüfungs-Anmeldung</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                 <form action="{{ route('forms.evaluations.store') }}" method="POST">
                    @csrf
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Antrag auf Zulassung zur Prüfung</h3>
                        </div>
                        <div class="card-body">
                            {{-- evaluation_type vielleicht anpassen? z.B. 'exam_application' --}}
                            <input type="hidden" name="evaluation_type" value="pruefung_anmeldung">
                            <input type="hidden" name="evaluation_date" value="{{ date('Y-m-d') }}">
                            <input type="hidden" name="period" value="N/A">

                            {{-- Lade ALLE verfügbaren Prüfungen --}}
                            @php
                                $availableExams = \App\Models\Exam::orderBy('title')->get();
                            @endphp

                            @if($availableExams->isEmpty())
                                <div class="alert alert-warning">
                                    Derzeit sind keine Prüfungen im System hinterlegt, für die ein Antrag gestellt werden kann.
                                </div>
                            @else
                                <div class="form-group">
                                    {{-- Feldname geändert --}}
                                    <label for="target_exam_id">Prüfung auswählen</label>
                                    {{-- Name geändert, Error-Key angepasst --}}
                                    <select name="target_exam_id" id="target_exam_id" class="form-control @error('target_exam_id') is-invalid @enderror" required>
                                        <option value="">Bitte auswählen...</option>
                                        @foreach($availableExams as $exam)
                                            <option value="{{ $exam->id }}" {{ old('target_exam_id') == $exam->id ? 'selected' : '' }}>
                                                {{ $exam->title }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('target_exam_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    <small class="form-text text-muted">Wähle die Prüfung aus, für die du dich anmelden möchtest.</small>
                                </div>
                            @endif

                            <div class="form-group">
                                <label for="description">Anmerkungen (z.B. Prüfungswiederholung, Wunschtermin)</label>
                                <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description') }}</textarea>
                                 @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="{{ route('forms.evaluations.index') }}" class="btn btn-secondary">Abbrechen</a>
                            <button type="submit" class="btn btn-primary float-right" @if($availableExams->isEmpty()) disabled @endif>
                                Prüfung beantragen
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
