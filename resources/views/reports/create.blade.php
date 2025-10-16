@extends('layouts.app')
@section('title', 'Neuen Einsatzbericht erstellen')
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

                <!-- NEU: Vorlagenauswahl -->
                <div class="form-group">
                    <label for="template_selector">Berichtsvorlage auswählen (optional)</label>
                    <select class="form-control" id="template_selector">
                        <option value="">-- Keine Vorlage --</option>
                        @foreach($templates as $key => $template)
                            <option value="{{ $key }}">{{ $template['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <hr>

                <div class="form-group">
                    <label for="title">Titel / Einsatzstichwort</label>
                    <input type="text" class="form-control" id="title" name="title" required>
                </div>

                <!-- NEU: Patientenauswahl mit Select2 -->
                <div class="form-group">
                    <label for="patient_name">Name des Patienten</label>
                    <select class="form-control select2" id="patient_name" name="patient_name" required>
                        <option value="">Patient auswählen oder Namen eingeben</option>
                        @foreach($citizens as $citizen)
                            <option value="{{ $citizen->name }}">{{ $citizen->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="location">Einsatzort</label>
                    <input type="text" class="form-control" id="location" name="location" required>
                </div>
                <div class="form-group">
                    <label for="incident_description">Einsatzhergang</label>
                    <textarea class="form-control" id="incident_description" name="incident_description" rows="5" required></textarea>
                </div>
                <div class="form-group">
                    <label for="actions_taken">Durchgeführte Maßnahmen</label>
                    <textarea class="form-control" id="actions_taken" name="actions_taken" rows="5" required></textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-flat">
                    <i class="fas fa-save me-1"></i> Bericht speichern
                </button>
                <a href="{{ route('reports.index') }}" class="btn btn-default btn-flat">Abbrechen</a>
            </form>
        </div>
    </div>
@endsection

@push('styles')
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@x.x.x/dist/select2-bootstrap4.min.css">
@endpush

@push('scripts')
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialisiere Select2 für die Patientenauswahl
            $('.select2').select2({
                theme: 'bootstrap4',
                tags: true, // Erlaubt die Eingabe von neuen Namen
                placeholder: 'Patient auswählen oder Namen eingeben',
            });

            // Logik für die Vorlagenauswahl
            const templates = @json($templates); // Vorlagen als JSON für JS verfügbar machen
            
            $('#template_selector').on('change', function() {
                const selectedKey = $(this).val();
                if (selectedKey && templates[selectedKey]) {
                    const template = templates[selectedKey];
                    $('#title').val(template.title);
                    $('#incident_description').val(template.incident_description);
                    $('#actions_taken').val(template.actions_taken);
                } else {
                    // Optional: Felder leeren, wenn "Keine Vorlage" ausgewählt wird
                    $('#title').val('');
                    $('#incident_description').val('');
                    $('#actions_taken').val('');
                }
            });
        });
    </script>
@endpush
