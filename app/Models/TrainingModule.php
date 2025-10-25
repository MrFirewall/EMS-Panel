<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingModule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'category',
    ];

    /**
     * Die Benutzer, die diesem Modul zugewiesen sind.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'training_module_user')
                    ->withPivot('assigned_by_user_id', 'completed_at', 'notes') // Zusatzinfos laden
                    ->withTimestamps(); // Lädt created_at (Zugewiesen am) und updated_at
    }

    /**
     * NEU: Die Prüfung, die zu diesem Modul gehört.
     */
    public function exam()
    {
        return $this->hasOne(Exam::class);
    }
}