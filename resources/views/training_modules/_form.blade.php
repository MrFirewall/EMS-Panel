@csrf
<div class="card-body">
    <div class="form-group">
        <label for="name">Name des Moduls</label>
        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" 
               value="{{ old('name', $module->name ?? '') }}" required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group">
        <label for="category">Kategorie</label>
        <input type="text" class="form-control @error('category') is-invalid @enderror" id="category" name="category" 
               value="{{ old('category', $module->category ?? '') }}" placeholder="z.B. Medizinisch, Taktisch, Führung">
        @error('category')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group">
        <label for="description">Beschreibung</label>
        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" 
                  rows="5">{{ old('description', $module->description ?? '') }}</textarea>
        @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
<!-- /.card-body -->
<div class="card-footer">
    <a href="{{ route('modules.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Abbrechen
    </a>
    <button type="submit" class="btn btn-primary float-right">
        <i class="fas fa-save"></i> Modul speichern
    </button>
</div>

