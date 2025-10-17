<?php

namespace App\Policies;

use App\Models\Prescription;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PrescriptionPolicy
{
    /**
     * Prüft, ob der Benutzer eine Liste von Rezepten ansehen darf.
     * (Normalerweise im Kontext eines Patienten)
     */
    public function viewAny(User $user): bool
    {
        // Erlaubt die Aktion, wenn der Benutzer die Berechtigung 'prescriptions.view' hat.
        return $user->can('prescriptions.view');
    }

    /**
     * Prüft, ob der Benutzer ein bestimmtes Rezept ansehen darf.
     */
    public function view(User $user, Prescription $prescription): bool
    {
        return $user->can('prescriptions.view');
    }

    /**
     * Prüft, ob der Benutzer ein neues Rezept erstellen darf.
     */
    public function create(User $user): bool
    {
        return $user->can('prescriptions.create');
    }

    /**
     * Prüft, ob der Benutzer ein bestehendes Rezept bearbeiten darf.
     * (Für den Fall, dass du später eine Bearbeiten-Funktion hinzufügst)
     */
    public function update(User $user, Prescription $prescription): bool
    {
        return $user->can('prescriptions.edit');
    }

    /**
     * Prüft, ob der Benutzer ein Rezept löschen (stornieren) darf.
     */
    public function delete(User $user, Prescription $prescription): bool
    {
        return $user->can('prescriptions.delete');
    }
}