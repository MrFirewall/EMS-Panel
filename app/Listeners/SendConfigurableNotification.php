<?php
// app/Listeners/SendConfigurableNotification.php

namespace App\Listeners;

use App\Events\PotentiallyNotifiableActionOccurred;
use App\Models\NotificationRule;
use App\Models\User;
use App\Models\Evaluation;
use App\Models\TrainingModule;
use App\Models\Announcement;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Citizen;
use App\Models\Prescription;
use App\Models\Report;
use App\Models\Vacation;
use App\Notifications\GeneralNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\ServiceRecord;


class SendConfigurableNotification
{
    /**
     * Handle the event.
     */
    public function handle(PotentiallyNotifiableActionOccurred $event): void
    {
        // --- GEÄNDERT: Regelempfang ---
        // Finde alle aktiven Regeln, bei denen die controller_action im JSON-Array enthalten ist
        $rules = NotificationRule::whereJsonContains('controller_action', $event->controllerAction)
                                     ->where('is_active', true)
                                     ->get();

        if ($rules->isEmpty()) {
            // Log::info("[Notify] Keine Regeln für {$event->controllerAction}.");
            return;
        }

        $recipients = collect();

        foreach ($rules as $rule) {
            // --- GEÄNDERT: $rule->target_identifier ist jetzt ein ARRAY! ---
            $identifiers = $rule->target_identifier;
            if (empty($identifiers) || !is_array($identifiers)) {
                continue; // Überspringe Regel ohne gültige Ziele
            }

            switch ($rule->target_type) {
                case 'role':
                    // Spatie's 'role()' Methode kann direkt ein Array von Namen verarbeiten
                    $usersWithRole = User::role($identifiers)->get();
                    $recipients = $recipients->merge($usersWithRole);
                    break;

                case 'permission':
                    // Spatie's 'permission()' Methode kann direkt ein Array von Namen verarbeiten
                    $usersWithPermission = User::permission($identifiers)->get();
                    $recipients = $recipients->merge($usersWithPermission);
                    break;

                case 'user':
                    // --- GEÄNDERT: Logik für 'user'-Typ ---

                    // Prüfe, ob 'triggering_user' im Array ist
                    if (in_array('triggering_user', $identifiers)) {
                        if ($event->triggeringUser instanceof User) {
                            $recipients->push($event->triggeringUser);
                        }
                    }

                    // Finde alle anderen Identifier, die User-IDs sind
                    $userIds = collect($identifiers)->filter(function ($id) {
                        // Filtere 'triggering_user' und alle nicht-numerischen Werte heraus
                        return is_numeric($id);
                    })->all();

                    if (!empty($userIds)) {
                        $users = User::whereIn('id', $userIds)->get();
                        $recipients = $recipients->merge($users);
                    }
                    break;
            }
        }

        // --- Empfänger-Filterung ---
        $actorUserId = $event->actorUser ? $event->actorUser->id : null;
        $triggeringUserId = ($event->triggeringUser instanceof User) ? $event->triggeringUser->id : null; // Nur wenn triggeringUser ein User ist

        // Regel: Schließe den AKTEUR aus, es sei denn, er wird explizit genannt ODER er ist derselbe wie der Triggering User (z.B. bei Antragstellung).
        // --- GEÄNDERT: Prüfung für Filterung ---
        $notifyActorUserRuleExists = $rules->contains(function ($rule) use ($actorUserId) {
            // Prüfe, ob die ID des Akteurs im Array der 'user'-Identifier enthalten ist
            return $rule->target_type === 'user' && is_array($rule->target_identifier) && in_array($actorUserId, $rule->target_identifier);
        });

        $uniqueRecipients = $recipients->unique('id');

        if ($actorUserId && !$notifyActorUserRuleExists && $actorUserId !== $triggeringUserId) {
            $uniqueRecipients = $uniqueRecipients->reject(function ($user) use ($actorUserId) {
                return $user->id === $actorUserId;
            });
        }


        if ($uniqueRecipients->isEmpty()) {
            // Log::info("[Notify] Keine Empfänger (nach Filterung) für {$event->controllerAction}.");
            return;
        }

        // --- Standard Benachrichtigungsdetails ---
        $actorName = $event->actorUser ? $event->actorUser->name : 'System';
        $notificationText = "Aktion ({$event->controllerAction}) ausgeführt von {$actorName}.";
        $notificationIcon = 'fas fa-info-circle text-info';
        $notificationUrl = route('dashboard'); // Fallback

        // --- Spezifische Logik pro Controller-Aktion ---

        // A) Antrag eingereicht (EvaluationController@store)
        if ($event->controllerAction === 'EvaluationController@store' && $event->relatedModel instanceof Evaluation) {
             /** @var Evaluation $evaluation */
            $evaluation = $event->relatedModel;
            $moduleName = $evaluation->json_data['module_name'] ?? '?';
            $antragArt = ($evaluation->evaluation_type === 'modul_anmeldung') ? 'Modulanmeldung' : 'Prüfungsanmeldung';
             /** @var User $antragsteller */
            $antragsteller = $event->triggeringUser; // Ist hier der User, der den Antrag stellt

            $notificationText = "Neuer Antrag ({$antragArt}) für '{$moduleName}' von {$antragsteller->name}.";
            $notificationIcon = 'fas fa-file-signature text-warning';
             $notificationUrl = route()->has('admin.forms.evaluations.show') // Prüfen ob Admin-Route existiert
                 ? route('admin.forms.evaluations.show', $evaluation->id)
                 : route('forms.evaluations.show', $evaluation->id);
            // Weiter zu Notification::send am Ende
        }

        // B) Benutzer Modul zugewiesen (TrainingAssignmentController@assign)
        elseif ($event->controllerAction === 'TrainingAssignmentController@assign' && $event->relatedModel instanceof TrainingModule) {
             /** @var TrainingModule $module */
            $module = $event->relatedModel;
             /** @var User $assignedUser */
            $assignedUser = $event->triggeringUser;
             /** @var User $assigningAdmin */
            $assigningAdmin = $event->actorUser;

             // Benachrichtige NUR den zugewiesenen Benutzer, falls eine Regel dafür existiert
             // --- GEÄNDERT: Prüfung auf Array ---
             $notifyAssignedUserRuleExists = $rules->contains(function ($rule) use ($assignedUser) {
                 return $rule->target_type === 'user' && is_array($rule->target_identifier) && in_array($assignedUser->id, $rule->target_identifier);
             });

             if($notifyAssignedUserRuleExists) {
                 $notificationTextUser = "Du wurdest von {$assigningAdmin->name} dem Modul '{$module->name}' zugewiesen.";
                 $notificationIconUser = 'fas fa-user-graduate text-success';
                 $notificationUrlUser = route('modules.show', $module->id);
                 Notification::send($assignedUser, new GeneralNotification($notificationTextUser, $notificationIconUser, $notificationUrlUser));
             }

             // Benachrichtige andere (Admins etc.)
             $otherRecipients = $uniqueRecipients->reject(fn($u) => $u->id === $assignedUser->id); // Empfänger ohne den zugewiesenen User
             if($otherRecipients->isNotEmpty()){
                 $notificationTextAdmin = "{$assignedUser->name} wurde von {$assigningAdmin->name} dem Modul '{$module->name}' zugewiesen.";
                 $notificationIconAdmin = 'fas fa-user-check text-info';
                 $notificationUrlAdmin = route('admin.users.show', $assignedUser->id);
                 Notification::send($otherRecipients, new GeneralNotification($notificationTextAdmin, $notificationIconAdmin, $notificationUrlAdmin));
             }
            return; // Beende hier, da Benachrichtigungen schon gesendet wurden
        }

        // C-E) Ankündigungen (AnnouncementController)
        elseif ($event->controllerAction === 'AnnouncementController@store' && $event->relatedModel instanceof Announcement) {
             /** @var Announcement $announcement */
            $announcement = $event->relatedModel;
             /** @var User $ersteller */
            $ersteller = $event->actorUser;
            $notificationText = "Neue Ankündigung '{$announcement->title}' wurde von {$ersteller->name} veröffentlicht.";
            $notificationIcon = 'fas fa-bullhorn text-primary';
            $notificationUrl = route('dashboard'); // Oder Link zur Ankündigung
             // Weiter zu Notification::send am Ende
        }
        elseif ($event->controllerAction === 'AnnouncementController@update' && $event->relatedModel instanceof Announcement) {
             /** @var Announcement $announcement */
            $announcement = $event->relatedModel;
             /** @var User $editor */
            $editor = $event->actorUser;
            $notificationText = "Ankündigung '{$announcement->title}' wurde von {$editor->name} bearbeitet.";
            $notificationIcon = 'fas fa-edit text-info';
            $notificationUrl = route('dashboard');
             // Weiter zu Notification::send am Ende
        }
        elseif ($event->controllerAction === 'AnnouncementController@destroy') {
            $title = $event->additionalData['title'] ?? 'Unbekannt';
             /** @var User $deleter */
            $deleter = $event->actorUser;
            $notificationText = "Ankündigung '{$title}' wurde von {$deleter->name} gelöscht.";
            $notificationIcon = 'fas fa-trash-alt text-danger';
            $notificationUrl = route('admin.announcements.index');
             // Weiter zu Notification::send am Ende
        }

        // F-K) Admin Prüfungsverwaltung (Admin\ExamController)
        elseif ($event->controllerAction === 'Admin\ExamController@store' && $event->relatedModel instanceof Exam) {
             /** @var Exam $exam */
            $exam = $event->relatedModel;
             /** @var User $creator */
            $creator = $event->actorUser;
            $notificationText = "Neue Prüfung '{$exam->title}' wurde von {$creator->name} erstellt.";
            $notificationIcon = 'fas fa-file-medical-alt text-success';
            $notificationUrl = route('admin.exams.show', $exam->id);
             // Weiter zu Notification::send am Ende
        }
        elseif ($event->controllerAction === 'Admin\ExamController@update' && $event->relatedModel instanceof Exam) {
             /** @var Exam $exam */
            $exam = $event->relatedModel;
              /** @var User $editor */
            $editor = $event->actorUser;
            $notificationText = "Prüfung '{$exam->title}' wurde von {$editor->name} bearbeitet.";
            $notificationIcon = 'fas fa-edit text-info';
            $notificationUrl = route('admin.exams.show', $exam->id);
             // Weiter zu Notification::send am Ende
        }
        elseif ($event->controllerAction === 'Admin\ExamController@destroy') {
            $title = $event->additionalData['title'] ?? 'Unbekannt';
             /** @var User $deleter */
            $deleter = $event->actorUser;
            $notificationText = "Prüfung '{$title}' wurde von {$deleter->name} gelöscht.";
            $notificationIcon = 'fas fa-trash-alt text-danger';
            $notificationUrl = route('admin.exams.index');
             // Weiter zu Notification::send am Ende
        }
        elseif ($event->controllerAction === 'Admin\ExamController@resetAttempt' && $event->relatedModel instanceof ExamAttempt) {
             /** @var ExamAttempt $attempt */
            $attempt = $event->relatedModel;
             /** @var User $resetter */
            $resetter = $event->actorUser;
             /** @var User $student */
            $student = $event->triggeringUser; // Der Schüler, dessen Versuch zurückgesetzt wird
            $notificationText = "Prüfungsversuch für '{$attempt->exam->title}' von {$student->name} wurde von {$resetter->name} zurückgesetzt.";
            $notificationIcon = 'fas fa-undo text-warning';
            $notificationUrl = route('admin.exams.attempts.index');
             // Weiter zu Notification::send am Ende (Benachrichtigt Admins/Prüfer)
        }
        elseif ($event->controllerAction === 'Admin\ExamController@setEvaluated' && $event->relatedModel instanceof ExamAttempt) {
             /** @var ExamAttempt $attempt */
            $attempt = $event->relatedModel;
             /** @var User $evaluator */
            $evaluator = $event->actorUser;
             /** @var User $student */
            $student = $event->triggeringUser;
            $resultText = $event->additionalData['isPassed'] ? 'bestanden' : 'nicht bestanden';
            $resultIcon = $event->additionalData['isPassed'] ? 'fas fa-check-circle text-success' : 'fas fa-times-circle text-danger';

            // Benachrichtige den Schüler, falls eine Regel existiert
            // --- GEÄNDERT: Prüfung auf Array ---
             $notifyStudentRuleExists = $rules->contains(function ($rule) use ($student) {
                return $rule->target_type === 'user' && is_array($rule->target_identifier) && in_array($student->id, $rule->target_identifier);
             });
             if($notifyStudentRuleExists){
                 $notificationTextStudent = "Deine Prüfung '{$attempt->exam->title}' wurde von {$evaluator->name} bewertet: {$resultText} ({$attempt->score}%).";
                 Notification::send($student, new GeneralNotification($notificationTextStudent, $resultIcon, route('exams.result', $attempt->uuid)));
             }

            // Benachrichtige andere Admins/Prüfer
            $otherRecipients = $uniqueRecipients->reject(fn($u) => $u->id === $student->id);
            if($otherRecipients->isNotEmpty()){
                 $notificationTextAdmin = "Prüfung '{$attempt->exam->title}' von {$student->name} wurde von {$evaluator->name} als '{$resultText}' bewertet ({$attempt->score}%).";
                 Notification::send($otherRecipients, new GeneralNotification($notificationTextAdmin, 'fas fa-clipboard-check text-info', route('admin.exams.attempts.index')));
            }
            return; // Beende hier
        }
        elseif ($event->controllerAction === 'Admin\ExamController@sendLink' && $event->relatedModel instanceof ExamAttempt) {
             /** @var ExamAttempt $attempt */
            $attempt = $event->relatedModel;
             /** @var User $user */
            $user = $event->triggeringUser; // Der User, FÜR den der Link ist
             /** @var User $admin */
            $admin = $event->actorUser; // Der Admin, der den Link generiert

            // Benachrichtige den User, für den der Link ist (falls Regel existiert)
            // --- GEÄNDERT: Prüfung auf Array ---
             $notifyUserRuleExists = $rules->contains(function ($rule) use ($user) {
                return $rule->target_type === 'user' && is_array($rule->target_identifier) && in_array($user->id, $rule->target_identifier);
             });
             if($notifyUserRuleExists) {
                 $notificationTextUser = "Ein Prüfungslink für '{$attempt->exam->title}' wurde von {$admin->name} für dich generiert.";
                 Notification::send($user, new GeneralNotification($notificationTextUser, 'fas fa-link text-info', route('exams.take', $attempt->uuid)));
             }

              // Benachrichtige andere (Admins etc.)
             $otherRecipients = $uniqueRecipients->reject(fn($u) => $u->id === $user->id);
             if($otherRecipients->isNotEmpty()){
                 $notificationTextAdmin = "Prüfungslink für '{$attempt->exam->title}' wurde von {$admin->name} für {$user->name} generiert.";
                 Notification::send($otherRecipients, new GeneralNotification($notificationTextAdmin, 'fas fa-link text-secondary', route('admin.exams.attempts.index')));
             }
            return; // Beende hier
        }

        // L-Q) Berechtigungen & Rollen (Admin)
        elseif ($event->controllerAction === 'Admin\PermissionController@store' && $event->relatedModel instanceof Permission) {
              /** @var Permission $permission */
            $permission = $event->relatedModel;
              /** @var User $creator */
            $creator = $event->actorUser;
            $notificationText = "Neue Berechtigung '{$permission->name}' wurde von {$creator->name} erstellt.";
            $notificationIcon = 'fas fa-key text-success';
            $notificationUrl = route('admin.permissions.index');
             // Weiter zu Notification::send am Ende
        }
        elseif ($event->controllerAction === 'Admin\PermissionController@update' && $event->relatedModel instanceof Permission) {
              /** @var Permission $permission */
            $permission = $event->relatedModel;
             /** @var User $editor */
            $editor = $event->actorUser;
            $notificationText = "Berechtigung '{$permission->name}' wurde von {$editor->name} bearbeitet.";
            $notificationIcon = 'fas fa-edit text-info';
            $notificationUrl = route('admin.permissions.index');
             // Weiter zu Notification::send am Ende
        }
        elseif ($event->controllerAction === 'Admin\PermissionController@destroy') {
            $name = $event->additionalData['name'] ?? 'Unbekannt';
             /** @var User $deleter */
            $deleter = $event->actorUser;
            $notificationText = "Berechtigung '{$name}' wurde von {$deleter->name} gelöscht.";
            $notificationIcon = 'fas fa-trash-alt text-danger';
            $notificationUrl = route('admin.permissions.index');
             // Weiter zu Notification::send am Ende
        }
        elseif ($event->controllerAction === 'Admin\RoleController@store' && $event->relatedModel instanceof Role) {
             /** @var Role $role */
            $role = $event->relatedModel;
             /** @var User $creator */
            $creator = $event->actorUser;
            $notificationText = "Neue Rolle '{$role->name}' wurde von {$creator->name} erstellt.";
            $notificationIcon = 'fas fa-user-shield text-success';
            $notificationUrl = route('admin.roles.index', ['role' => $role->id]);
             // Weiter zu Notification::send am Ende
        }
        elseif ($event->controllerAction === 'Admin\RoleController@update' && $event->relatedModel instanceof Role) {
             /** @var Role $role */
            $role = $event->relatedModel;
             /** @var User $editor */
            $editor = $event->actorUser;
            $notificationText = "Rolle '{$role->name}' wurde von {$editor->name} bearbeitet.";
            $notificationIcon = 'fas fa-edit text-info';
            $notificationUrl = route('admin.roles.index', ['role' => $role->id]);
             // Weiter zu Notification::send am Ende
        }
        elseif ($event->controllerAction === 'Admin\RoleController@destroy') {
            $name = $event->additionalData['name'] ?? 'Unbekannt';
             /** @var User $deleter */
            $deleter = $event->actorUser;
            $notificationText = "Rolle '{$name}' wurde von {$deleter->name} gelöscht.";
            $notificationIcon = 'fas fa-trash-alt text-danger';
            $notificationUrl = route('admin.roles.index');
             // Weiter zu Notification::send am Ende
        }

        // R-T) Benutzerverwaltung & Akteneinträge (Admin)
        elseif ($event->controllerAction === 'Admin\UserController@store' && $event->relatedModel instanceof User) {
             /** @var User $newUser */
            $newUser = $event->relatedModel;
             /** @var User $creator */
            $creator = $event->actorUser;
            $notificationText = "Neuer Benutzer '{$newUser->name}' wurde von {$creator->name} angelegt.";
            $notificationIcon = 'fas fa-user-plus text-success';
            $notificationUrl = route('admin.users.show', $newUser->id);
             // Weiter zu Notification::send am Ende
        }
        elseif ($event->controllerAction === 'Admin\UserController@update' && $event->relatedModel instanceof User) {
             /** @var User $editedUser */
            $editedUser = $event->relatedModel;
             /** @var User $editor */
            $editor = $event->actorUser;
            $notificationText = "Profil von '{$editedUser->name}' wurde von {$editor->name} bearbeitet.";
            $notificationIcon = 'fas fa-user-edit text-info';
            $notificationUrl = route('admin.users.show', $editedUser->id);
             // Weiter zu Notification::send am Ende
        }
        elseif ($event->controllerAction === 'Admin\UserController@addRecord' && $event->relatedModel instanceof ServiceRecord) {
             /** @var ServiceRecord $record */
            $record = $event->relatedModel;
             /** @var User $targetUser */
            $targetUser = $event->triggeringUser; // Der User, der den Eintrag erhält
             /** @var User $author */
            $author = $event->actorUser;
            $notificationText = "Neuer Akteneintrag ('{$record->type}') wurde von {$author->name} für {$targetUser->name} hinzugefügt.";
            $notificationIcon = 'fas fa-folder-plus text-primary';
            $notificationUrl = route('admin.users.show', $targetUser->id);
             // Weiter zu Notification::send am Ende
        }

        // U-W) Patientenakten (CitizenController)
        elseif ($event->controllerAction === 'CitizenController@store' && $event->relatedModel instanceof Citizen) {
             /** @var Citizen $citizen */
            $citizen = $event->relatedModel;
             /** @var User $creator */
            $creator = $event->actorUser;
            $notificationText = "Neue Patientenakte für '{$citizen->name}' wurde von {$creator->name} erstellt.";
            $notificationIcon = 'fas fa-address-book text-success';
            $notificationUrl = route('citizens.show', $citizen->id);
             // Weiter zu Notification::send am Ende
        }
        elseif ($event->controllerAction === 'CitizenController@update' && $event->relatedModel instanceof Citizen) {
              /** @var Citizen $citizen */
            $citizen = $event->relatedModel;
              /** @var User $editor */
            $editor = $event->actorUser;
            $notificationText = "Patientenakte von '{$citizen->name}' wurde von {$editor->name} bearbeitet.";
            $notificationIcon = 'fas fa-edit text-info';
            $notificationUrl = route('citizens.show', $citizen->id);
             // Weiter zu Notification::send am Ende
        }
        elseif ($event->controllerAction === 'CitizenController@destroy') {
            $name = $event->additionalData['name'] ?? 'Unbekannt';
             /** @var User $deleter */
            $deleter = $event->actorUser;
            $notificationText = "Patientenakte von '{$name}' wurde von {$deleter->name} gelöscht.";
            $notificationIcon = 'fas fa-trash-alt text-danger';
            $notificationUrl = route('citizens.index');
             // Weiter zu Notification::send am Ende
        }

        // X-Y) Dienststatus (DutyStatusController)
        elseif ($event->controllerAction === 'DutyStatusController@toggle.on_duty' && $event->triggeringUser instanceof User) {
             /** @var User $user */
            $user = $event->triggeringUser;
            $notificationText = "{$user->name} hat den Dienst angetreten.";
            $notificationIcon = 'fas fa-user-clock text-success';
            $notificationUrl = route('admin.users.show', $user->id);
             // Weiter zu Notification::send am Ende
        }
        elseif ($event->controllerAction === 'DutyStatusController@toggle.off_duty' && $event->triggeringUser instanceof User) {
             /** @var User $user */
            $user = $event->triggeringUser;
            $notificationText = "{$user->name} hat den Dienst beendet.";
            $notificationIcon = 'fas fa-user-clock text-danger';
            $notificationUrl = route('admin.users.show', $user->id);
             // Weiter zu Notification::send am Ende
        }

        // Z-BB) Prüfungsaktionen (ExamController, non-admin)
        elseif ($event->controllerAction === 'ExamController@generateLink' && $event->relatedModel instanceof ExamAttempt) {
             /** @var ExamAttempt $attempt */
            $attempt = $event->relatedModel;
             /** @var User $user */
            $user = $event->triggeringUser;
             /** @var User $admin */
            $admin = $event->actorUser;

            // Benachrichtige den User, für den der Link ist (falls Regel existiert)
            // --- GEÄNDERT: Prüfung auf Array ---
            $notifyUserRuleExists = $rules->contains(fn($r) => $r->target_type === 'user' && is_array($r->target_identifier) && in_array($user->id, $r->target_identifier));
            if ($notifyUserRuleExists) {
                 $nText = "Ein Prüfungslink für '{$attempt->exam->title}' wurde von {$admin->name} für dich generiert.";
                 Notification::send($user, new GeneralNotification($nText, 'fas fa-link text-info', route('exams.take', $attempt->uuid)));
            }
            // Benachrichtige andere
            $otherRecipients = $uniqueRecipients->reject(fn($u) => $u->id === $user->id);
            if ($otherRecipients->isNotEmpty()) {
                 $nText = "Prüfungslink für '{$attempt->exam->title}' wurde von {$admin->name} für {$user->name} generiert.";
                 Notification::send($otherRecipients, new GeneralNotification($nText, 'fas fa-link text-secondary', route('admin.exams.attempts.index')));
            }
            return; // Beende hier
        }
        elseif ($event->controllerAction === 'ExamController@submit' && $event->relatedModel instanceof ExamAttempt) {
              /** @var ExamAttempt $attempt */
            $attempt = $event->relatedModel;
              /** @var User $user */
            $user = $event->triggeringUser; // Ist hier der Actor UND der Triggering User

            // Benachrichtige User (falls Regel existiert)
            // --- GEÄNDERT: Prüfung auf Array ---
            $notifyUserRuleExists = $rules->contains(fn($r) => $r->target_type === 'user' && is_array($r->target_identifier) && in_array($user->id, $r->target_identifier));
              if ($notifyUserRuleExists) {
                 $nText = "Deine Prüfung '{$attempt->exam->title}' wurde eingereicht.";
                 Notification::send($user, new GeneralNotification($nText, 'fas fa-paper-plane text-success', route('exams.submitted')));
            }
            // Benachrichtige Admins/Prüfer
             $otherRecipients = $uniqueRecipients->reject(fn($u) => $u->id === $user->id);
            if ($otherRecipients->isNotEmpty()) {
                 $nText = "{$user->name} hat Prüfung '{$attempt->exam->title}' eingereicht (Score: {$attempt->score}%).";
                 Notification::send($otherRecipients, new GeneralNotification($nText, 'fas fa-file-alt text-warning', route('admin.exams.result', $attempt->uuid)));
            }
            return; // Beende hier
        }
        elseif ($event->controllerAction === 'ExamController@finalizeEvaluation' && $event->relatedModel instanceof ExamAttempt) {
              /** @var ExamAttempt $attempt */
            $attempt = $event->relatedModel;
              /** @var User $student */
            $student = $event->triggeringUser;
              /** @var User $admin */
            $admin = $event->actorUser;
            $resultText = $event->additionalData['isPassed'] ? 'bestanden' : 'nicht bestanden';
            $resultIcon = $event->additionalData['isPassed'] ? 'fas fa-check-circle text-success' : 'fas fa-times-circle text-danger';

            // Benachrichtige den Schüler (falls Regel existiert)
            // --- GEÄNDERT: Prüfung auf Array ---
            $notifyStudentRuleExists = $rules->contains(fn($r) => $r->target_type === 'user' && is_array($r->target_identifier) && in_array($student->id, $r->target_identifier));
            if ($notifyStudentRuleExists) {
                 $nText = "Deine Prüfung '{$attempt->exam->title}' wurde von {$admin->name} bewertet: {$resultText} ({$attempt->score}%).";
                 Notification::send($student, new GeneralNotification($nText, $resultIcon, route('modules.show', $attempt->exam->training_module_id)));
            }
            // Benachrichtige andere Admins
             $otherRecipients = $uniqueRecipients->reject(fn($u) => $u->id === $student->id);
            if ($otherRecipients->isNotEmpty()) {
                 $nText = "Prüfung '{$attempt->exam->title}' von {$student->name} wurde von {$admin->name} als '{$resultText}' bewertet ({$attempt->score}%).";
                 Notification::send($otherRecipients, new GeneralNotification($nText, 'fas fa-clipboard-check text-info', route('admin.exams.attempts.index')));
            }
            return; // Beende hier
        }

        // CC-DD) Rezeptverwaltung (PrescriptionController)
        elseif ($event->controllerAction === 'PrescriptionController@store' && $event->relatedModel instanceof Prescription) {
              /** @var Prescription $prescription */
            $prescription = $event->relatedModel;
              /** @var Citizen $citizen */
            $citizen = $event->triggeringUser; // Ist hier der Citizen
              /** @var User $doctor */
            $doctor = $event->actorUser;
            $citizenName = ($citizen instanceof Citizen) ? $citizen->name : 'Unbekannt';
            $notificationText = "Neues Rezept ('{$prescription->medication}') wurde von {$doctor->name} für {$citizenName} ausgestellt.";
            $notificationIcon = 'fas fa-prescription-bottle-alt text-success';
            $notificationUrl = ($citizen instanceof Citizen) ? route('citizens.show', [$citizen->id, 'tab' => 'prescriptions']) : route('citizens.index');
        }
        elseif ($event->controllerAction === 'PrescriptionController@destroy') {
              // relatedModel ist hier ein Objekt mit den alten Daten
              $medication = $event->additionalData['name'] ?? ($event->relatedModel->medication ?? 'Unbekannt');
              /** @var Citizen $citizen */
              $citizenName = $event->additionalData['citizen_name'] ?? 'Unbekannt';
             /** @var User $doctor */
            $doctor = $event->actorUser;
            $notificationText = "Rezept ('{$medication}') für {$citizenName} wurde von {$doctor->name} storniert.";
            $notificationIcon = 'fas fa-trash-alt text-danger';
            // Versuche, die Citizen-ID aus dem relatedModel zu bekommen, falls vorhanden
            $citizenId = $event->relatedModel->citizen_id ?? null;
            $notificationUrl = $citizenId ? route('citizens.show', [$citizenId, 'tab' => 'prescriptions']) : route('citizens.index'); // Fallback
             // Weiter zu Notification::send am Ende
        }
        
        // EE-GG) Einsatzberichte (ReportController)
        elseif ($event->controllerAction === 'ReportController@store' && $event->relatedModel instanceof Report) {
            $report = $event->relatedModel; $creator = $event->actorUser;
            $patientName = $report->patient_name;
            $notificationText = "Neuer Einsatzbericht ('{$report->title}') wurde von {$creator->name} erstellt (Patient: {$patientName}).";
            $notificationIcon = 'fas fa-file-medical text-success'; $notificationUrl = route('reports.show', $report->id);
        }
        elseif ($event->controllerAction === 'ReportController@update' && $event->relatedModel instanceof Report) {
            $report = $event->relatedModel; $editor = $event->actorUser;
            $notificationText = "Einsatzbericht '{$report->title}' wurde von {$editor->name} bearbeitet.";
            $notificationIcon = 'fas fa-edit text-info'; $notificationUrl = route('reports.show', $report->id);
        }
        elseif ($event->controllerAction === 'ReportController@destroy') {
            $title = $event->additionalData['title'] ?? 'Unbekannt';
            $patientName = $event->additionalData['patient_name'] ?? 'Unbekannt';
            $deleter = $event->actorUser;
            $notificationText = "Einsatzbericht '{$title}' (Patient: {$patientName}) wurde von {$deleter->name} gelöscht.";
            $notificationIcon = 'fas fa-trash-alt text-danger'; $notificationUrl = route('reports.index');
        }

        // HH-JJ) Ausbildungsmodule (TrainingModuleController) - NEU
        elseif ($event->controllerAction === 'TrainingModuleController@store' && $event->relatedModel instanceof TrainingModule) {
             /** @var TrainingModule $module */
            $module = $event->relatedModel;
             /** @var User $creator */
            $creator = $event->actorUser;
            $notificationText = "Neues Ausbildungsmodul '{$module->name}' wurde von {$creator->name} erstellt.";
            $notificationIcon = 'fas fa-graduation-cap text-success';
            $notificationUrl = route('modules.show', $module->id); // Link zum neuen Modul
        }
        elseif ($event->controllerAction === 'TrainingModuleController@update' && $event->relatedModel instanceof TrainingModule) {
             /** @var TrainingModule $module */
            $module = $event->relatedModel;
             /** @var User $editor */
            $editor = $event->actorUser;
            $notificationText = "Ausbildungsmodul '{$module->name}' wurde von {$editor->name} bearbeitet.";
            $notificationIcon = 'fas fa-edit text-info';
            $notificationUrl = route('modules.show', $module->id); // Link zum Modul
        }
        elseif ($event->controllerAction === 'TrainingModuleController@destroy') {
            $name = $event->additionalData['name'] ?? 'Unbekannt';
              /** @var User $deleter */
            $deleter = $event->actorUser;
            $notificationText = "Ausbildungsmodul '{$name}' wurde von {$deleter->name} gelöscht.";
            $notificationIcon = 'fas fa-trash-alt text-danger';
            $notificationUrl = route('modules.index'); // Link zur Modulübersicht
        }
        // KK) Benutzer meldet sich für Modul an (Antrag)
        elseif ($event->controllerAction === 'TrainingModuleController@signUp' && $event->relatedModel instanceof TrainingModule) {
              /** @var TrainingModule $module */
            $module = $event->relatedModel;
              /** @var User $user */
            $user = $event->triggeringUser; // Ist hier der User, der sich anmeldet (und Actor)
            $notificationText = "{$user->name} hat sich für das Modul '{$module->name}' angemeldet (Antrag).";
            $notificationIcon = 'fas fa-user-plus text-warning'; // Ähnlich wie Antrag
            // Link zur Benutzerseite oder Modulseite? Hier Link zur Modulseite.
            $notificationUrl = route('modules.show', $module->id);
        }

        // MM-NN) Urlaubsanträge (VacationController) - NEU
        elseif ($event->controllerAction === 'VacationController@store' && $event->relatedModel instanceof Vacation) {
             /** @var Vacation $vacation */
            $vacation = $event->relatedModel;
             /** @var User $requester */
            $requester = $event->triggeringUser; // Ist hier der Actor UND Triggering User
            $notificationText = "Neuer Urlaubsantrag von {$requester->name} ({$vacation->start_date->format('d.m.Y')} - {$vacation->end_date->format('d.m.Y')}).";
            $notificationIcon = 'fas fa-plane-departure text-warning';
            $notificationUrl = route('admin.vacations.index'); // Link zur Admin-Übersicht
        }
        elseif ($event->controllerAction === 'VacationController@updateStatus' && $event->relatedModel instanceof Vacation) {
             /** @var Vacation $vacation */
            $vacation = $event->relatedModel;
             /** @var User $user */
            $user = $event->triggeringUser; // Der User, dessen Antrag bearbeitet wurde
             /** @var User $admin */
            $admin = $event->actorUser; // Der Admin, der den Status geändert hat
            $status = $event->additionalData['status'] ?? 'unbekannt'; // 'approved' oder 'rejected'
            $statusText = $status === 'approved' ? 'genehmigt' : 'abgelehnt';
            $statusIcon = $status === 'approved' ? 'fas fa-check-circle text-success' : 'fas fa-times-circle text-danger';

            // Benachrichtige den Antragsteller (falls Regel existiert)
            // --- GEÄNDERT: Prüfung auf Array ---
            $notifyUserRuleExists = $rules->contains(fn($r) => $r->target_type === 'user' && is_array($r->target_identifier) && in_array($user->id, $r->target_identifier));
            if ($notifyUserRuleExists) {
                 $nTextU = "Dein Urlaubsantrag ({$vacation->start_date->format('d.m.Y')} - {$vacation->end_date->format('d.m.Y')}) wurde von {$admin->name} {$statusText}.";
                 Notification::send($user, new GeneralNotification($nTextU, $statusIcon, route('profile.show'))); // Link zum eigenen Profil
            }

            // Benachrichtige andere Admins etc. (falls Regel existiert)
            $otherRecipients = $uniqueRecipients->reject(fn($u) => $u->id === $user->id);
            if ($otherRecipients->isNotEmpty()) {
                 $nTextA = "Urlaubsantrag von {$user->name} wurde von {$admin->name} {$statusText}.";
                 Notification::send($otherRecipients, new GeneralNotification($nTextA, 'fas fa-calendar-check text-info', route('admin.vacations.index')));
            }
            return; // Beende hier, da Benachrichtigungen gesendet wurden
        }


        // --- Fallback oder gemeinsamer Sendeaufruf ---
        if (!empty($notificationText) && $uniqueRecipients->isNotEmpty()) {
             // Log::info("[Notify] Sende Benachrichtigung für {$event->controllerAction} an {$uniqueRecipients->count()} Empfänger.");
            Notification::send($uniqueRecipients, new GeneralNotification($notificationText, $notificationIcon, $notificationUrl));
        } else {
             // Log::warning("[Notify] Keine Empfänger oder spezifische Benachrichtigungslogik für {$event->controllerAction} gefunden.");
        }
    }
}