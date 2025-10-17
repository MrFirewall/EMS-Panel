<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prescription extends Model
{
    use HasFactory;

    protected $fillable = [
        'citizen_id',
        'user_id',
        'medication',
        'dosage',
        'notes',
    ];

    public function citizen()
    {
        return $this->belongsTo(Citizen::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}