@extends('layouts.app')

@section('title', 'Bürgerakte bearbeiten')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <h1 class="m-0">Bürgerakte bearbeiten: {{ $citizen->name }}</h1>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card card-outline card-primary">
        <div class="card-body">
            <form method="POST" action="{{ route('citizens.update', $citizen) }}">
                @csrf
                @method('PUT')
                @include('citizens._form')
                <button type="submit" class="btn btn-primary btn-flat">
                    <i class="fas fa-save me-1"></i> Änderungen speichern
                </button>
                <a href="{{ route('citizens.index') }}" class="btn btn-default btn-flat">Abbrechen</a>
            </form>
        </div>
    </div>
@endsection
@push('scripts')
<script>
    // Funktion zum Hinzufügen von Text zu einer Textarea
    function appendToTextarea(textareaId, selectedItems) {
        if (selectedItems.length === 0) return;

        const textarea = $('#' + textareaId);
        let existingText = textarea.val();
        let newText = selectedItems.join(', ');

        // Füge ein Komma hinzu, wenn bereits Text vorhanden ist
        if (existingText.length > 0 && !existingText.endsWith(' ') && !existingText.endsWith(',')) {
            existingText += ', ';
        }

        textarea.val(existingText + newText);
    }

    // Event-Handler für Allergien-Modal
    $('#selectAllergiesBtn').on('click', function() {
        let selected = [];
        $('#allergiesModal .form-check-input:checked').each(function() {
            selected.push($(this).val());
        });
        appendToTextarea('allergies', selected);
        $('#allergiesModal').modal('hide');
    });

    // Event-Handler für Vorerkrankungen-Modal
    $('#selectConditionsBtn').on('click', function() {
        let selected = [];
        $('#conditionsModal .form-check-input:checked').each(function() {
            selected.push($(this).val());
        });
        appendToTextarea('preexisting_conditions', selected);
        $('#conditionsModal').modal('hide');
    });

    // Setzt die Checkboxen zurück, wenn ein Modal geschlossen wird
    $('.modal').on('hidden.bs.modal', function () {
        $(this).find('.form-check-input').prop('checked', false);
    });
</script>
@endpush