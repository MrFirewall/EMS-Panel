<?php

namespace App\Policies;

use App\Models\Evaluation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EvaluationPolicy
{
    /**
     * Prüft, ob der Benutzer die Übersichtsseite der Bewertungen ansehen darf.
     * Jeder, der entweder alle oder seine eigenen Bewertungen sehen darf, hat Zugriff.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('evaluations.view.all') || $user->can('evaluations.view.own');
    }

    /**
     * Prüft, ob der Benutzer eine spezifische Bewertung ansehen darf.
     */
    public function view(User $user, Evaluation $evaluation): bool
    {
        // Zugriff erlauben, wenn:
        // 1. Der User die globale Berechtigung hat, alles zu sehen.
        if ($user->can('evaluations.view.all')) {
            return true;
        }

        // 2. Der User der Ersteller der Bewertung ist.
        if ($user->id === $evaluation->evaluator_id) {
            return true;
        }

        // 3. Der User der Empfänger der Bewertung ist UND die Berechtigung hat, eigene zu sehen.
        if ($user->id === $evaluation->user_id && $user->can('evaluations.view.own')) {
            return true;
        }

        return false;
    }

    /**
     * Prüft, ob der Benutzer neue Bewertungen/Formulare erstellen darf.
     */
    public function create(User $user): bool
    {
        return $user->can('evaluations.create');
    }

    /**
     * Platzhalter: Prüft, ob der Benutzer eine Bewertung bearbeiten darf.
     * (Für zukünftige Erweiterungen)
     */
    public function update(User $user, Evaluation $evaluation): bool
    {
        // Zum Beispiel: Nur der Ersteller oder ein Admin darf bearbeiten.
        return $user->id === $evaluation->evaluator_id || $user->can('evaluations.edit');
    }

    /**
     * Platzhalter: Prüft, ob der Benutzer eine Bewertung löschen darf.
     */
    public function delete(User $user, Evaluation $evaluation): bool
    {
        return $user->can('evaluations.delete');
    }
}