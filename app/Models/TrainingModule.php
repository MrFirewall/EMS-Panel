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
                    ->withPivot('status', 'completed_at', 'notes') // Diese Zusatzinfos laden
                    ->withTimestamps();
    }
} 