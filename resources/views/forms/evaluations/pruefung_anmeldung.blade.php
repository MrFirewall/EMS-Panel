@extends('layouts.app')
@section('title', 'Formular: Prüfungs-Anmeldung')

@section('content')
<div class="container-fluid">
    <!-- Seitenüberschrift und Breadcrumbs -->
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>Prüfungs-Anmeldung</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('forms.evaluations.index') }}">Formulare</a></li>
                <li class="breadcrumb-item active">Prüfungs-Anmeldung</li>
            </ol>
        </div>
    </div>

    <!-- Hauptinhalt der Seite -->
    <div class="row">
        <div class="col-md-8 offset-md-2">
             <form action="{{ route('forms.evaluations.store') }}" method="POST">
                @csrf
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Antrag auf Zulassung zur Abschlussprüfung</h3>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <input type="hidden" name="evaluation_type" value="{{ $evaluationType }}">
                        <input type="hidden" name="evaluation_date" value="{{ date('Y-m-d') }}">
                        <input type="hidden" name="period" value="N/A">

                        @if($modules->isEmpty())
                            <div class="alert alert-warning">
                                Du bist derzeit für keine Module angemeldet, für die eine Prüfung abgelegt werden könnte.
                            </div>
                        @else
                            <div class="form-group">
                                <label for="target_module_id">Prüfung für Modul</label>
                                <select name="target_module_id" id="target_module_id" class="form-control @error('target_module_id') is-invalid @enderror" required>
                                    <option value="">Bitte auswählen...</option>
                                    @foreach($modules as $module)
                                        <option value="{{ $module->id }}">{{ $module->name }} (Aktueller Status: {{ str_replace('_', ' ', ucfirst($module->pivot->status)) }})</option>
                                    @endforeach
                                </select>
                                @error('target_module_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        @endif

                        <div class="form-group">
                            <label for="description">Anmerkungen (z.B. Wunschtermin für die Prüfung)</label>
                            <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description') }}</textarea>
                             @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <!-- /.card-body -->
                    <div class="card-footer">
                        <a href="{{ route('forms.evaluations.index') }}" class="btn btn-secondary">Abbrechen</a>
                        <button type="submit" class="btn btn-primary float-right" @if($modules->isEmpty()) disabled @endif>
                            Prüfung beantragen
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

