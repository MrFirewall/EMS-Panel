<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'target_id',
        'log_type',
        'action',
        'description',
        'details',
    ];

    /**
     * Ruft den Benutzer ab, der das Log erstellt hat.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * NEU: Gibt den Namen des Log-Erstellers zurück.
     * Zeigt "System" für Dienst-Einträge (DUTY_START/DUTY_END) an.
     *
     * @return string
     */
    public function getCreatorNameAttribute(): string
    {
        return $this->user->name ?? 'Unbekannt';
    }
}
