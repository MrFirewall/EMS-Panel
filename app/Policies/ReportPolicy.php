<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ReportPolicy
{
    /**
     * Erlaubt einem Admin, alles zu tun.
     */
    public function before(User $user, string $ability): bool|null
    {
        if ($user->can('reports.manage.all')) {
            return true;
        }
        return null; // Wichtig: null, damit andere Methoden geprüft werden
    }

    /**
     * Darf der Benutzer die Liste der Berichte sehen?
     */
    public function viewAny(User $user): bool
    {
        return $user->can('reports.view');
    }

    /**
     * Darf der Benutzer einen spezifischen Bericht ansehen?
     */
    public function view(User $user, Report $report): bool
    {
        return $user->can('reports.view');
    }

    /**
     * Darf der Benutzer einen neuen Bericht erstellen?
     */
    public function create(User $user): bool
    {
        return $user->can('reports.create');
    }

    /**
     * Darf der Benutzer einen Bericht aktualisieren?
     */
    public function update(User $user, Report $report): bool
    {
        // Erlaubt, wenn der User der Autor ist UND die 'edit' Berechtigung hat.
        return $user->id === $report->user_id && $user->can('reports.edit');
    }

    /**
     * Darf der Benutzer einen Bericht löschen?
     */
    public function delete(User $user, Report $report): bool
    {
        // Erlaubt, wenn der User der Autor ist UND die 'delete' Berechtigung hat.
        return $user->id === $report->user_id && $user->can('reports.delete');
    }
}