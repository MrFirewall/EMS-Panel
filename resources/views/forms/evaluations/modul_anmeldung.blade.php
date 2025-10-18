@extends('layouts.app')
@section('title', 'Formular: Modul-Anmeldung')

@section('content')
<div class="container-fluid">
    <!-- Seitenüberschrift und Breadcrumbs -->
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>Modul-Anmeldung</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('forms.evaluations.index') }}">Formulare</a></li>
                <li class="breadcrumb-item active">Modul-Anmeldung</li>
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
                        <h3 class="card-title">Antrag auf Zuweisung zu einem Ausbildungsmodul</h3>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <input type="hidden" name="evaluation_type" value="{{ $evaluationType }}">
                        <input type="hidden" name="evaluation_date" value="{{ date('Y-m-d') }}">
                        <input type="hidden" name="period" value="N/A">

                        @if($modules->isEmpty())
                            <div class="alert alert-warning">
                                Es sind keine neuen Module verfügbar, für die du dich anmelden könntest, oder du bist bereits allen Modulen zugewiesen.
                            </div>
                        @else
                            <div class="form-group">
                                <label for="target_module_id">Gewünschtes Modul</label>
                                <select name="target_module_id" id="target_module_id" class="form-control @error('target_module_id') is-invalid @enderror" required>
                                    <option value="">Bitte auswählen...</option>
                                    @foreach($modules as $module)
                                        <option value="{{ $module->id }}">{{ $module->name }} ({{ $module->category ?? 'Allgemein' }})</option>
                                    @endforeach
                                </select>
                                @error('target_module_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        @endif

                        <div class="form-group">
                            <label for="description">Begründung (Optional)</label>
                            <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description') }}</textarea>
                             @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <!-- /.card-body -->
                    <div class="card-footer">
                        <a href="{{ route('forms.evaluations.index') }}" class="btn btn-secondary">Abbrechen</a>
                        <button type="submit" class="btn btn-primary float-right" @if($modules->isEmpty()) disabled @endif>
                            Antrag absenden
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

 