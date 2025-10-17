{{-- Persönliche Daten --}}
<h5 class="mt-4 mb-3">Persönliche Daten</h5>
<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="name">Vollständiger Name</label>
            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $citizen->name ?? '') }}" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="date_of_birth">Geburtsdatum</label>
            <input type="date" class="form-control @error('date_of_birth') is-invalid @enderror" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth', $citizen->date_of_birth ?? '') }}">
            @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="phone_number">Telefonnummer</label>
            <input type="text" class="form-control @error('phone_number') is-invalid @enderror" id="phone_number" name="phone_number" value="{{ old('phone_number', $citizen->phone_number ?? '') }}">
            @error('phone_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="address">Adresse</label>
            <input type="text" class="form-control @error('address') is-invalid @enderror" id="address" name="address" value="{{ old('address', $citizen->address ?? '') }}">
            @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

{{-- Medizinische Daten --}}
<h5 class="mt-4 mb-3">Medizinische Stammdaten</h5>
<div class="row">
    <div class="col-md-4">
        {{-- NEU: Dropdown für Blutgruppe --}}
        <div class="form-group">
            <label for="blood_type">Blutgruppe</label>
            <select class="form-control @error('blood_type') is-invalid @enderror" id="blood_type" name="blood_type">
                <option value="" {{ old('blood_type', $citizen->blood_type ?? '') == '' ? 'selected' : '' }}>Nicht bekannt</option>
                <option value="A+" {{ old('blood_type', $citizen->blood_type ?? '') == 'A+' ? 'selected' : '' }}>A+</option>
                <option value="A-" {{ old('blood_type', $citizen->blood_type ?? '') == 'A-' ? 'selected' : '' }}>A-</option>
                <option value="B+" {{ old('blood_type', $citizen->blood_type ?? '') == 'B+' ? 'selected' : '' }}>B+</option>
                <option value="B-" {{ old('blood_type', $citizen->blood_type ?? '') == 'B-' ? 'selected' : '' }}>B-</option>
                <option value="AB+" {{ old('blood_type', $citizen->blood_type ?? '') == 'AB+' ? 'selected' : '' }}>AB+</option>
                <option value="AB-" {{ old('blood_type', $citizen->blood_type ?? '') == 'AB-' ? 'selected' : '' }}>AB-</option>
                <option value="0+" {{ old('blood_type', $citizen->blood_type ?? '') == '0+' ? 'selected' : '' }}>0+</option>
                <option value="0-" {{ old('blood_type', $citizen->blood_type ?? '') == '0-' ? 'selected' : '' }}>0-</option>
            </select>
            @error('blood_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="emergency_contact_name">Notfallkontakt (Name)</label>
            <input type="text" class="form-control @error('emergency_contact_name') is-invalid @enderror" id="emergency_contact_name" name="emergency_contact_name" value="{{ old('emergency_contact_name', $citizen->emergency_contact_name ?? '') }}">
            @error('emergency_contact_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
     <div class="col-md-4">
        <div class="form-group">
            <label for="emergency_contact_phone">Notfallkontakt (Telefon)</label>
            <input type="text" class="form-control @error('emergency_contact_phone') is-invalid @enderror" id="emergency_contact_phone" name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $citizen->emergency_contact_phone ?? '') }}">
            @error('emergency_contact_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>
<div class="form-group">
    <label for="allergies">Allergien & Unverträglichkeiten</label>
    <div class="input-group">
        <textarea class="form-control @error('allergies') is-invalid @enderror" id="allergies" name="allergies" rows="3">{{ old('allergies', $citizen->allergies ?? '') }}</textarea>
        <div class="input-group-append">
            <button class="btn btn-outline-secondary" type="button" data-toggle="modal" data-target="#allergiesModal">Vorlage...</button>
        </div>
    </div>
    @error('allergies')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="form-group">
    <label for="preexisting_conditions">Vorerkrankungen</label>
    <div class="input-group">
        <textarea class="form-control @error('preexisting_conditions') is-invalid @enderror" id="preexisting_conditions" name="preexisting_conditions" rows="3">{{ old('preexisting_conditions', $citizen->preexisting_conditions ?? '') }}</textarea>
        <div class="input-group-append">
             <button class="btn btn-outline-secondary" type="button" data-toggle="modal" data-target="#conditionsModal">Vorlage...</button>
        </div>
    </div>
    @error('preexisting_conditions')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="form-group">
    <label for="notes">Allgemeine Notizen</label>
    <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="4">{{ old('notes', $citizen->notes ?? '') }}</textarea>
    @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="modal fade" id="allergiesModal" tabindex="-1" role="dialog" aria-labelledby="allergiesModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="allergiesModalLabel">Allergien aus Vorlage auswählen</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        {{-- Passe diese Liste nach deinen RP-Bedürfnissen an --}}
        <div class="form-check"><input class="form-check-input" type="checkbox" value="Pollenallergie (Heuschnupfen)"> Pollenallergie (Heuschnupfen)</div>
        <div class="form-check"><input class="form-check-input" type="checkbox" value="Hausstaubmilbenallergie"> Hausstaubmilbenallergie</div>
        <div class="form-check"><input class="form-check-input" type="checkbox" value="Tierhaarallergie (z.B. Katzen, Hunde)"> Tierhaarallergie</div>
        <div class="form-check"><input class="form-check-input" type="checkbox" value="Schimmelpilzallergie"> Schimmelpilzallergie</div>
        <div class="form-check"><input class="form-check-input" type="checkbox" value="Nahrungsmittelallergie (z.B. Nüsse, Laktose)"> Nahrungsmittelallergie</div>
        <div class="form-check"><input class="form-check-input" type="checkbox" value="Penicillin-Allergie"> Penicillin-Allergie</div>
        <div class="form-check"><input class="form-check-input" type="checkbox" value="Insektengiftallergie (Bienen, Wespen)"> Insektengiftallergie</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Abbrechen</button>
        <button type="button" class="btn btn-primary" id="selectAllergiesBtn">Auswahl übernehmen</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="conditionsModal" tabindex="-1" role="dialog" aria-labelledby="conditionsModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="conditionsModalLabel">Vorerkrankungen aus Vorlage auswählen</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        {{-- Passe diese Liste nach deinen RP-Bedürfnissen an --}}
        <div class="form-check"><input class="form-check-input" type="checkbox" value="Asthma bronchiale"> Asthma bronchiale</div>
        <div class="form-check"><input class="form-check-input" type="checkbox" value="Bluthochdruck (Hypertonie)"> Bluthochdruck (Hypertonie)</div>
        <div class="form-check"><input class="form-check-input" type="checkbox" value="Diabetes Mellitus Typ 1"> Diabetes Mellitus Typ 1</div>
        <div class="form-check"><input class="form-check-input" type="checkbox" value="Diabetes Mellitus Typ 2"> Diabetes Mellitus Typ 2</div>
        <div class="form-check"><input class="form-check-input" type="checkbox" value="Herzerkrankung (z.B. KHK)"> Herzerkrankung (z.B. KHK)</div>
        <div class="form-check"><input class="form-check-input" type="checkbox" value="Chronische Migräne"> Chronische Migräne</div>
        <div class="form-check"><input class="form-check-input" type="checkbox" value="Epilepsie"> Epilepsie</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Abbrechen</button>
        <button type="button" class="btn btn-primary" id="selectConditionsBtn">Auswahl übernehmen</button>
      </div>
    </div>
  </div>
</div>