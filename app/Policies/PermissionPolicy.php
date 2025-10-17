<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Permission; // Wichtig: Das Model von Spatie verwenden
use Illuminate\Auth\Access\Response;

class PermissionPolicy
{
    /**
     * Prüft, ob der Benutzer die Liste der Berechtigungen ansehen darf.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('permissions.view');
    }

    /**
     * Prüft, ob der Benutzer eine neue Berechtigung erstellen darf.
     */
    public function create(User $user): bool
    {
        return $user->can('permissions.create');
    }

    /**
     * Prüft, ob der Benutzer eine bestehende Berechtigung bearbeiten darf.
     */
    public function update(User $user, Permission $permission): bool
    {
        return $user->can('permissions.edit');
    }

    /**
     * Prüft, ob der Benutzer eine Berechtigung löschen darf.
     */
    public function delete(User $user, Permission $permission): bool
    {
        return $user->can('permissions.delete');
    }
}