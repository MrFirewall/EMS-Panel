<?php

namespace App\Policies;

use App\Models\NotificationRule;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NotificationRulePolicy
{
    use HandlesAuthorization;

    /**
     * Bestimmt, ob der Benutzer alle Benachrichtigungsregeln anzeigen darf.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user): bool
    {
        // Erlaubt, wenn der Benutzer die Berechtigung hat
        return $user->can('notification.rules.manage');
    }

    /**
     * Bestimmt, ob der Benutzer eine spezifische Benachrichtigungsregel anzeigen darf.
     * (Normalerweise nicht benötigt für Resource-Controller ohne 'show'-Route)
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\NotificationRule  $notificationRule
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, NotificationRule $notificationRule): bool
    {
        // Erlaubt, wenn der Benutzer die Berechtigung hat
        return $user->can('notification.rules.manage');
    }

    /**
     * Bestimmt, ob der Benutzer neue Benachrichtigungsregeln erstellen darf.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user): bool
    {
        // Erlaubt, wenn der Benutzer die Berechtigung hat
        return $user->can('notification.rules.manage');
    }

    /**
     * Bestimmt, ob der Benutzer eine Benachrichtigungsregel aktualisieren darf.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\NotificationRule  $notificationRule
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, NotificationRule $notificationRule): bool
    {
        // Erlaubt, wenn der Benutzer die Berechtigung hat
        return $user->can('notification.rules.manage');
    }

    /**
     * Bestimmt, ob der Benutzer eine Benachrichtigungsregel löschen darf.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\NotificationRule  $notificationRule
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, NotificationRule $notificationRule): bool
    {
        // Erlaubt, wenn der Benutzer die Berechtigung hat
        return $user->can('notification.rules.manage');
    }

    /**
     * Bestimmt, ob der Benutzer eine Benachrichtigungsregel wiederherstellen darf.
     * (Normalerweise nur relevant, wenn Soft Deletes verwendet werden)
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\NotificationRule  $notificationRule
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, NotificationRule $notificationRule): bool
    {
         // Erlaubt, wenn der Benutzer die Berechtigung hat
        return $user->can('notification.rules.manage');
    }

    /**
     * Bestimmt, ob der Benutzer eine Benachrichtigungsregel endgültig löschen darf.
     * (Normalerweise nur relevant, wenn Soft Deletes verwendet werden)
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\NotificationRule  $notificationRule
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, NotificationRule $notificationRule): bool
    {
         // Erlaubt, wenn der Benutzer die Berechtigung hat
        return $user->can('notification.rules.manage');
    }
}
