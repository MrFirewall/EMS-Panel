<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'citizen_id',
        'title',
        'patient_name',
        'incident_description',
        'actions_taken',
        'location',
    ];

    /**
     * Definiert die Beziehung zum User-Model.
     * Ein Bericht gehört zu einem Benutzer.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    /**
     * Definiert die Beziehung zum Citizen-Model.
     * Ein Bericht gehört zu einem Bürger.
     */
    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class, 'citizen_id');
    }
    /**
     * Definiert die Many-to-Many-Beziehung zu den Usern (Mitarbeitern), die an dem Bericht beteiligt sind.
     */
    public function attendingStaff()
    {
        return $this->belongsToMany(User::class, 'report_user');
    }
}