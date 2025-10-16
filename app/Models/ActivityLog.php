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
        // Wenn der Log-Typ DUTY_START oder DUTY_END ist, gib "System" zurück.
        if (in_array($this->log_type, ['DUTY_START', 'DUTY_END'])) {
            return 'System';
        }

        // Andernfalls, gib den Namen des verknüpften Benutzers zurück.
        // Falls kein Benutzer verknüpft ist (z.B. user_id ist null), gib einen Fallback-Wert an.
        return $this->user->name ?? 'Unbekannt';
    }
}
