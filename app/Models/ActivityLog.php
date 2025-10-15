<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'log_type',
        'action',
        'target_id',
        'description',
    ];

    /**
     * Relation zum Benutzer, der die Aktion ausgeführt hat.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}