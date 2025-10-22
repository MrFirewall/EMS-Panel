<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
    use HasFactory;

    /**
     * Die Attribute, die massenzuweisbar sind.
     * Bewertungstyp (z.B. 'azubi', 'leitstelle', 'mitarbeiter', 'praktikant') wird benötigt, um die Daten zu unterscheiden.
     */
    protected $fillable = [
        'user_id',          // Der bewertete Benutzer
        'evaluator_id',     // Der Benutzer, der die Bewertung erstellt hat
        'evaluation_type',  // Der Typ der Bewertung (z.B. 'azubi')
        'period',           // Bewertungszeitraum (z.B. '00-06 Uhr')
        'json_data',        // Speichert alle spezifischen Bewertungskriterien als JSON
        'description',      // Freitextbeschreibung / Kommentar
        'status',           // Status der Bewertung (z.B. 'pending', 'processed')
    ];

    protected $casts = [
        'json_data' => 'array',
    ];

    /**
     * Beziehung zum bewerteten Benutzer.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Beziehung zum Ersteller der Bewertung.
     */
    public function evaluator()
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }
}
