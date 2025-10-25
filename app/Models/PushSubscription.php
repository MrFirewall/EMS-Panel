<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PushSubscription extends Model {
    protected $fillable = ['endpoint', 'public_key', 'auth_token']; // HINZUFÜGEN

    public function user() { // HINZUFÜGEN
        return $this->belongsTo(User::class);
    }
}