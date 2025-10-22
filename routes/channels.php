<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\User;

// Dieser private Kanal stellt sicher, dass nur der angemeldete Benutzer (user)
// Events auf dem users.{id}-Kanal empfangen kann.
Broadcast::channel('users.{userId}', function (User $user, int $userId) {
    return (int) $user->id === (int) $userId;
});

