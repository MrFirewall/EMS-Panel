<?php
// app/Models/NotificationRule.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_description',
        'controller_action',
        'target_type',
        'target_identifier',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
