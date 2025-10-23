<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'controller_action',
        'target_type',
        'target_identifier',
        'event_description',
        'is_active',
    ];

    /**
     * Die Attribute, die umgewandelt werden sollen.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'controller_action' => 'array', // NEU
        'target_identifier' => 'array', // NEU
    ];
}