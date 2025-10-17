<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Citizen extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'date_of_birth',
        'phone_number',
        'address',
        'notes',
        'blood_type',
        'allergies',
        'preexisting_conditions',
        'emergency_contact_name',
        'emergency_contact_phone',
    ];
    /**
     * Definiert die "hasMany"-Beziehung zu den Berichten (Reports).
     * Ein Bürger kann viele Berichte haben.
     */
    public function reports(): HasMany
    {
        // Annahme: Dein Berichts-Model heißt 'Report'
        // Laravel erwartet eine 'citizen_id' Spalte in deiner 'reports' Tabelle.
        return $this->hasMany(Report::class);
    }
    public function prescriptions()
    {
        return $this->hasMany(Prescription::class)->latest(); // Sortiert die Rezepte, das Neuste zuerst
    }
}
