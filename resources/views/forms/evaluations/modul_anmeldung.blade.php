@extends('layouts.app')
@section('title', 'Formular: Modul-Anmeldung')

@section('content')
    {{-- AdminLTE Content Header --}}
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><i class="fas fa-edit nav-icon"></i> Formular: Modul-Anmeldung</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('forms.evaluations.index') }}">Formulare</a></li>
                        <li class="breadcrumb-item active">Modul-Anmeldung</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-8 offset-lg-2 col-md-10 offset-md-1 col-sm-12">
                    {{-- Das Formular umschließt die gesamte Karte --}}
                    <form action="{{ route('forms.evaluations.store') }}" method="POST">
                        @csrf
                        <div class="card card-primary card-outline">
                            <div class="card-header">
                                <h3 class="card-title">Antrag auf Zuweisung zu einem Ausbildungsmodul</h3>
                            </div>
                            <div class="card-body">
                                {{-- Versteckte Felder zur Identifizierung des Formulars --}}
                                <input type="hidden" name="evaluation_type" value="{{ $evaluationType }}">
                                <input type="hidden" name="evaluation_date" value="{{ date('Y-m-d') }}">
                                <input type="hidden" name="period" value="N/A">

                                @if($modules->isEmpty())
                                    <div class="alert alert-warning">
                                        <h5 class="alert-heading"><i class="icon fas fa-info-circle"></i> Keine Module verfügbar</h5>
                                        Es sind derzeit keine neuen Module verfügbar, für die du dich anmelden könntest, oder du bist bereits allen Modulen zugewiesen.
                                    </div>
                                @else
                                    <div class="form-group">
                                        <label for="target_module_id">Gewünschtes Modul</label>
                                        <select name="target_module_id" id="target_module_id" class="form-control @error('target_module_id') is-invalid @enderror" required>
                                            <option value="" disabled selected>Bitte auswählen...</option>
                                            @foreach($modules as $module)
                                                <option value="{{ $module->id }}" {{ old('target_module_id') == $module->id ? 'selected' : '' }}>
                                                    {{ $module->name }} ({{ $module->category ?? 'Allgemein' }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('target_module_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                @endif

                                <div class="form-group">
                                    <label for="description">Begründung (Optional)</label>
                                    <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" rows="4" placeholder="Füge hier eine kurze Begründung für deinen Antrag hinzu...">{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="card-footer">
                                <a href="{{ route('forms.evaluations.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Abbrechen
                                </a>
                                <button type="submit" class="btn btn-primary float-right" @if($modules->isEmpty()) disabled @endif>
                                    <i class="fas fa-paper-plane"></i> Antrag absenden
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

