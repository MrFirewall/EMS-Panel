@extends('layouts.app')
@section('title', 'Neuen Einsatzbericht erstellen')

@push('styles')
    <!-- Select2 für durchsuchbare Dropdowns -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
@endpush

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <h1 class="m-0">Neuen Einsatzbericht erstellen</h1>
                </div>
            </div>
        </div>
    </div>
    <div class="card card-outline card-primary">
        <div class="card-body">
            <form method="POST" action="{{ route('reports.store') }}">
                @csrf

                <!-- VORLAGENAUSWAHL -->
                <div class="form-group row align-items-center">
                    <label for="template-selector" class="col-sm-3 col-form-label">Vorlage auswählen (optional)</label>
                    <div class="col-sm-9">
                        <select class="form-control" id="template-selector">
                            <option value="">-- Keine Vorlage --</option>
                            @foreach($templates as $key => $template)
                                <option value="{{ $key }}">{{ $template['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <hr>

                <div class="form-group">
                    <label for="title">Titel / Einsatzstichwort</label>
                    <input type="text" class="form-control" id="title" name="title" value="{{ old('title') }}" required>
                </div>

                <div class="form-group">
                    <label for="patient_name">Name des Patienten</label>
                    <select class="form-control" id="patient_name" name="patient_name" required>
                        <option value="">Bürger auswählen oder neuen Namen eingeben</option>
                        @foreach($citizens as $citizen)
                            <option value="{{ $citizen->name }}" {{ old('patient_name') == $citizen->name ? 'selected' : '' }}>{{ $citizen->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="location">Einsatzort</label>
                    <input type="text" class="form-control" id="location" name="location" value="{{ old('location') }}" required>
                </div>

                <div class="form-group">
                    <label for="incident_description">Einsatzhergang</label>
                    <textarea class="form-control" id="incident_description" name="incident_description" rows="5" required>{{ old('incident_description') }}</textarea>
                </div>

                <div class="form-group">
                    <label for="actions_taken">Durchgeführte Maßnahmen</label>
                    <textarea class="form-control" id="actions_taken" name="actions_taken" rows="5" required>{{ old('actions_taken') }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-flat">
                    <i class="fas fa-save me-1"></i> Bericht speichern
                </button>
                <a href="{{ route('reports.index') }}" class="btn btn-default btn-flat">Abbrechen</a>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <!-- Wichtig: jQuery muss VOR Select2 geladen werden -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        // Speichert die Vorlagen-Daten sicher als JSON-Objekt
        const templates = @json($templates);

        $(document).ready(function() {
            // Initialisiert die durchsuchbare Dropdown-Liste für Bürger
            $('#patient_name').select2({
                theme: 'bootstrap-4',
                placeholder: 'Bürger suchen oder Namen eingeben',
                tags: true // Erlaubt die Eingabe von neuen Namen, die nicht in der Liste sind
            });
            
            // Event Listener für die Vorlagenauswahl
            $('#template-selector').on('change', function() {
                const selectedKey = $(this).val();
                
                if (selectedKey && templates[selectedKey]) {
                    const template = templates[selectedKey];
                    // Füllt die Felder mit den Daten aus der Vorlage
                    $('#title').val(template.title);
                    $('#incident_description').val(template.incident_description);
                    $('#actions_taken').val(template.actions_taken);
                } else {
                    // Setzt die Felder zurück, wenn "Keine Vorlage" ausgewählt wird
                    $('#title').val('');
                    $('#incident_description').val('');
                    $('#actions_taken').val('');
                }
            });
        });
    </script>
@endpush

