@extends('layouts.app')
@section('title', 'Neue Ankündigung')
@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <h1 class="m-0"><i class="fas fa-bullhorn me-2"></i> Neue Ankündigung erstellen</h1>
                </div>
            </div>
        </div>
    </div>
        
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.announcements.store') }}">
                @csrf
                
                <div class="form-group">
                    <label for="title">Titel</label>
                    <input type="text" class="form-control" id="title" name="title" value="{{ old('title') }}" required>
                </div>
                
                <div class="form-group">
                    <label for="content">Inhalt</label>
                    <textarea class="form-control" id="content" name="content" rows="5" required>{{ old('content') }}</textarea>
                </div>
                
                {{-- BS5 form-switch ersetzt durch AdminLTE icheck-primary --}}
                <div class="form-group clearfix mt-4">
                    <div class="icheck-primary d-inline">
                        <input type="checkbox" id="is_active" name="is_active" checked {{ old('is_active') ? 'checked' : '' }}>
                        <label for="is_active">Ankündigung ist aktiv/sichtbar</label>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-flat mt-3">
                    <i class="fas fa-save me-1"></i> Speichern
                </button>
            </form>
        </div>
    </div>
@endsection
