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
    <textarea class="form-control @error('allergies') is-invalid @enderror" id="allergies" name="allergies" rows="3">{{ old('allergies', $citizen->allergies ?? '') }}</textarea>
    @error('allergies')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="form-group">
    <label for="preexisting_conditions">Vorerkrankungen</label>
    <textarea class="form-control @error('preexisting_conditions') is-invalid @enderror" id="preexisting_conditions" name="preexisting_conditions" rows="3">{{ old('preexisting_conditions', $citizen->preexisting_conditions ?? '') }}</textarea>
    @error('preexisting_conditions')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="form-group">
    <label for="notes">Allgemeine Notizen</label>
    <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="4">{{ old('notes', $citizen->notes ?? '') }}</textarea>
    @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>