<?php

namespace App\Policies;

use App\Models\Citizen;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CitizenPolicy
{
    /**
     * Prüft, ob der Benutzer die Bürgerliste ansehen darf.
     */
    public function viewAny(User $user): bool
    {
        // Erlaubt die Aktion, wenn der Benutzer die Berechtigung 'citizens.view' hat.
        return $user->can('citizens.view');
    }

    /**
     * Prüft, ob der Benutzer eine Bürgerakte sehen darf.
     */
    public function view(User $user, Citizen $citizen): bool
    {
        return $user->can('citizens.view');
    }

    /**
     * Prüft, ob der Benutzer eine neue Bürgerakte erstellen darf.
     */
    public function create(User $user): bool
    {
        return $user->can('citizens.create');
    }

    /**
     * Prüft, ob der Benutzer eine Bürgerakte bearbeiten darf.
     */
    public function update(User $user, Citizen $citizen): bool
    {
        return $user->can('citizens.edit');
    }

    /**
     * Prüft, ob der Benutzer eine Bürgerakte löschen darf.
     */
    public function delete(User $user, Citizen $citizen): bool
    {
        return $user->can('citizens.delete');
    }
}
