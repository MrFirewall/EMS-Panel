@extends('layouts.app')

@section('title', 'Ankündigung bearbeiten')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Ankündigung #{{ $announcement->id }} bearbeiten</h1>
                </div>
                <div class="col-sm-6 text-right">
                    <a href="{{ route('admin.announcements.index') }}" class="btn btn-default btn-flat">
                        <i class="fas fa-arrow-left"></i> Zurück zur Übersicht
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.announcements.update', $announcement) }}">
                @csrf
                @method('PUT') {{-- Wichtig: Sagt Laravel, dass dies eine Update-Aktion ist --}}

                <div class="form-group">
                    <label for="title">Titel</label>
                    <input type="text" class="form-control" id="title" name="title" value="{{ old('title', $announcement->title) }}" required>
                </div>

                <div class="form-group">
                    <label for="content">Inhalt</label>
                    <textarea class="form-control" id="content" name="content" rows="5" required>{{ old('content', $announcement->content) }}</textarea>
                </div>

                {{-- BS5 form-switch ersetzt durch AdminLTE icheck-primary --}}
                <div class="form-group clearfix mt-4">
                    <div class="icheck-primary d-inline">
                        {{-- Prüft, ob der alte Wert (bei Validierungsfehler) oder der DB-Wert "true" ist --}}
                        <input type="checkbox" id="is_active" name="is_active" @if(old('is_active', $announcement->is_active)) checked @endif>
                        <label for="is_active">Ankündigung ist aktiv/sichtbar</label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-flat mt-3">
                    <i class="fas fa-save me-1"></i> Änderungen speichern
                </button>
            </form>
        </div>
    </div>
@endsection
