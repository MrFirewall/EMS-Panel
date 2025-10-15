<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
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
}