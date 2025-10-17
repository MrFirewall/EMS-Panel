@extends('layouts.app')
@section('title', 'Rezept ausstellen für ' . $citizen->name)

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h4>
                        <i class="fas fa-pills"></i> Rezept ausstellen für: <strong>{{ $citizen->name }}</strong>
                    </h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('prescriptions.store', $citizen) }}" method="POST">
                        @csrf

                        {{-- NEU: VORLAGEN-DROPDOWN --}}
                        @if (!empty($templates))
                            <div class="form-group">
                                <label for="template-selector">Vorlage auswählen</label>
                                <select id="template-selector" class="form-control">
                                    <option value="">Bitte eine Vorlage auswählen...</option>
                                    @foreach ($templates as $key => $template)
                                        <option value="{{ $key }}"
                                                data-medication="{{ $template['name'] }}"
                                                data-dosage="{{ $template['dosage'] }}"
                                                data-notes="{{ $template['notes'] }}">
                                            {{ $template['name'] }} | {{ $template['dosage'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <hr>
                        @endif

                        <div class="form-group">
                            <label for="medication">Medikament</label>
                            <input type="text" class="form-control @error('medication') is-invalid @enderror" id="medication" name="medication" value="{{ old('medication') }}" required>
                            @error('medication')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label for="dosage">Dosierung</label>
                            <input type="text" class="form-control @error('dosage') is-invalid @enderror" id="dosage" name="dosage" value="{{ old('dosage') }}" placeholder="z.B. 1-0-1, 500mg morgens" required>
                            @error('dosage')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label for="notes">Hinweise zur Einnahme</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mt-4">
                            <a href="{{ route('citizens.show', $citizen) }}" class="btn btn-secondary">Abbrechen</a>
                            <button type="submit" class="btn btn-primary">Rezept ausstellen</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

{{-- NEU: JAVASCRIPT ZUM AUSFÜLLEN DER FELDER --}}
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const templateSelector = document.getElementById('template-selector');
        if (templateSelector) {
            templateSelector.addEventListener('change', function () {
                const selectedOption = this.options[this.selectedIndex];
                if (!selectedOption.value) {
                    // Reset fields if the placeholder is selected
                    document.getElementById('medication').value = '';
                    document.getElementById('dosage').value = '';
                    document.getElementById('notes').value = '';
                    return;
                }
                // Populate fields from data attributes
                document.getElementById('medication').value = selectedOption.dataset.medication;
                document.getElementById('dosage').value = selectedOption.dataset.dosage;
                document.getElementById('notes').value = selectedOption.dataset.notes;
            });
        }
    });
</script>
@endpush