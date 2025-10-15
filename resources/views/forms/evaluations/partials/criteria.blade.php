@php
    // Zugriff auf statische Variablen des Controllers
    $grades = App\Http\Controllers\EvaluationController::$grades;
    $periods = App\Http\Controllers\EvaluationController::$periods;
    
    // Definiere die Kriterien-Sets
    $allGrades = $grades;
    $yesNo = ['Ja', 'Nein', 'Nicht feststellbar'];
    
    // Allgemeine Kriterien
    $commonCriteria = [
        'Verhalten_im_Dienst' => 'Verhalten im Dienst',
        'Umgang_mit_Mitarbeitern' => 'Umgang mit Mitarbeitern',
        'Umgang_mit_Kunden' => 'Umgang mit Kunden',
        'Roleplay' => 'Roleplay',
        'Kompetenz' => 'Kompetenz',
    ];
    
    // Kriterien spezifisch für Azubi/Mitarbeiter (Fahrlizenzen)
    $drivingCriteria = [
        'Fahrverhalten' => 'Fahrverhalten',
        'Verkehrsabsicherung' => 'Verkehrsabsicherung',
    ];

    // Kriterien spezifisch für Leitstelle
    $dispatchCriteria = [
        'Einhaltung_der_Funkdisziplin' => 'Einhaltung der Funkdisziplin',
        'Erreichbarkeit_im_Funk' => 'Erreichbarkeit im Funk',
        'Fahrzeugverteilung' => 'Fahrzeugverteilung',
        'Auftragsverteilung' => 'Auftragsverteilung',
        'Durchsetzungsvermögen' => 'Durchsetzungsvermögen',
    ];
    
    // Finales Kriterien-Set basierend auf Typ
    $criteriaSet = [];
    $titleSuffix = '';

    switch ($evaluationType) {
        case 'azubi':
            $criteriaSet = array_merge($commonCriteria, $drivingCriteria);
            $criteriaSet['Alleine_fahren_lassen'] = 'Würden Sie den Azubi alleine fahren lassen';
            $titleSuffix = 'Azubis';
            break;
        case 'praktikant':
            $criteriaSet = [
                'Verhalten_im_Praktikum' => 'Verhalten im Praktikum',
                'Umgang_mit_Mitarbeitern' => 'Umgang mit Mitarbeitern',
                'Umgang_mit_Kunden' => 'Umgang mit Kunden',
                'Roleplay' => 'Roleplay',
                'Fahrzeugfuehrung' => 'Fahrzeugführung',
                'Kompetenz' => 'Kompetenz',
            ];
            $titleSuffix = 'Praktikanten';
            break;
        case 'mitarbeiter':
            $criteriaSet = array_merge($commonCriteria, $drivingCriteria);
            $titleSuffix = 'Mitarbeiters';
            break;
        case 'leitstelle':
            $criteriaSet = array_merge($dispatchCriteria, ['Umgang_mit_Mitarbeitern' => 'Umgang mit Mitarbeitern']);
            $titleSuffix = 'Mitarbeiters (Leitstelle)';
            break;
    }
@endphp

{{-- Die erste Zeile des Formulars --}}
<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label for="target_name">Name des {{ $titleSuffix }}</label>
            
            @if($evaluationType === 'praktikant')
                {{-- Manuelle Eingabe für Praktikanten --}}
                <input type="text" id="target_name" name="target_name" class="form-control @error('target_name') is-invalid @enderror" 
                       value="{{ old('target_name') }}" required 
                       placeholder="Name des Praktikanten manuell eingeben">
                @error('target_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <input type="hidden" name="user_id" value="0">
                
            @else
                {{-- Dropdown für registrierte Benutzer --}}
                <select id="user_id" name="user_id" class="form-control @error('user_id') is-invalid @enderror" required>
                    <option value="" selected disabled>Auswählen</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                    @endforeach
                </select>
                @error('user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                
            @endif
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label for="evaluation_date">Datum der Bewertung</label>
            <input type="date" id="evaluation_date" name="evaluation_date" class="form-control @error('evaluation_date') is-invalid @enderror" 
                   value="{{ old('evaluation_date', date('Y-m-d')) }}" required>
            @error('evaluation_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label for="period">Zu bewertender Zeitraum</label>
            <select id="period" name="period" class="form-control @error('period') is-invalid @enderror" required>
                <option value="" selected disabled>Auswählen</option>
                @foreach($periods as $period)
                    <option value="{{ $period }}" {{ old('period') == $period ? 'selected' : '' }}>{{ $period }}</option>
                @endforeach
            </select>
            @error('period')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="col-12"><hr class="mb-3 mt-3"></div>

    @foreach($criteriaSet as $key => $label)
        <div class="col-md-6">
            <div class="form-group">
                <label for="data_{{ $key }}">{{ $label }}</label>
                <select id="data_{{ $key }}" name="data[{{ $key }}]" class="form-control @error('data.'.$key) is-invalid @enderror" required>
                    <option value="" selected disabled>Auswählen</option>
                    @if($key === 'Alleine_fahren_lassen')
                        @foreach($yesNo as $grade)
                            <option value="{{ $grade }}" {{ old('data.'.$key) == $grade ? 'selected' : '' }}>{{ $grade }}</option>
                        @endforeach
                    @else
                        @foreach($allGrades as $grade)
                            <option value="{{ $grade }}" {{ old('data.'.$key) == $grade ? 'selected' : '' }}>{{ $grade }}</option>
                        @endforeach
                    @endif
                </select>
                @error('data.'.$key)<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    @endforeach
    
    <div class="col-12"><hr class="mb-3 mt-3"></div>

    <div class="col-12">
        <div class="form-group">
            <label for="description">Beschreibung / Kommentar</label>
            <textarea id="description" name="description" class="form-control" rows="4">{{ old('description') }}</textarea>
        </div>
    </div>

    <div class="col-12 mt-3">
        <button type="submit" class="btn btn-primary btn-flat"><i class="fas fa-save me-2"></i> Bewertung absenden</button>
    </div>
</div>