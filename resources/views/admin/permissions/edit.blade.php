@extends('layouts.app')

@section('title', 'Berechtigung bearbeiten')

@section('content')
<div class="card card-warning">
    <div class="card-header">
        <h3 class="card-title">Berechtigung bearbeiten</h3>
    </div>
    <form action="{{ route('admin.permissions.update', $permission) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="card-body">
            <div class="form-group">
                <label for="name">Name der Berechtigung</label>
                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $permission->name) }}" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>            
            <div class="form-group">
                <label for="description">Beschreibung (Anzeigename)</label>
                <input type="text" name="description" id="description" class="form-control @error('description') is-invalid @enderror"
                       value="{{ old('description', $permission->description) }}" placeholder="z.B. Artikel verwalten">
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Änderungen speichern
            </button>
            <a href="{{ route('admin.permissions.index') }}" class="btn btn-secondary">Abbrechen</a>
        </div>
    </form>
</div>
@endsection