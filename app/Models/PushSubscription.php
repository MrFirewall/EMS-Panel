<?php

namespace App\Models;

// Nutze die Klasse des Pakets als Alias
use NotificationChannels\WebPush\PushSubscription as WebPushPackageSubscription;

// Dein Model MUSS die Klasse des Pakets erweitern (extend)
class PushSubscription extends WebPushPackageSubscription 
{
    // Die Basisklasse erbt bereits von Model.
    // Wir definieren fillable neu, um sicherzustellen, dass die Daten gespeichert werden können.
    
    protected $fillable = [
        'endpoint', 
        'public_key', 
        'auth_token', 
        'user_id'
    ];
    
    // Keine weiteren Anpassungen nötig.
}