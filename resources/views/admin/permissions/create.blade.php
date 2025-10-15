@extends('layouts.app')

@section('title', 'Neue Berechtigung erstellen')

@section('content')
<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">Neue Berechtigung</h3>
    </div>
    <form action="{{ route('admin.permissions.store') }}" method="POST">
        @csrf
        <div class="card-body">
            <div class="form-group">
                <label for="name">Name der Berechtigung (z.B. "users.edit")</label>
                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name') }}" placeholder="z.B. manage.articles" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="description">Beschreibung (Anzeigename)</label>
                <input type="text" name="description" id="description" class="form-control @error('description') is-invalid @enderror"
                       value="{{ old('description') }}" placeholder="z.B. Artikel verwalten">
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Speichern</button>
            <a href="{{ route('admin.permissions.index') }}" class="btn btn-secondary">Abbrechen</a>
        </div>
    </form>
</div>
@endsection