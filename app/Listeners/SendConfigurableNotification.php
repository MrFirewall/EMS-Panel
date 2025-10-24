<?php

namespace App\Listeners;

use App\Events\PotentiallyNotifiableActionOccurred;
use App\Models\NotificationRule;
use App\Models\User;
use App\Models\Evaluation;
use App\Models\TrainingModule;
use App\Models\Announcement;
use App\Models\Exam; // Import sicherstellen
use App\Models\ExamAttempt; // Import sicherstellen
use App\Models\Citizen;
use App\Models\Prescription;
use App\Models\Report;
use App\Models\Vacation;
use App\Models\ServiceRecord;
use App\Notifications\GeneralNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log; // Optional für Debugging
use Illuminate\Support\Facades\Route; // Wichtig für Route::has()
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;


class SendConfigurableNotification
{
    /**
     * Handle the event.
     */
    public function handle(PotentiallyNotifiableActionOccurred $event): void
    {
        // Finde alle aktiven Regeln, bei denen die controller_action im JSON-Array enthalten ist
        $rules = NotificationRule::whereJsonContains('controller_action', $event->controllerAction)
                                     ->where('is_active', true)
                                     ->get();

        if ($rules->isEmpty()) {
            // Log::info("[Notify] Keine Regeln für {$event->controllerAction}.");
            return;
        }

        $recipients = collect();

        // Empfänger basierend auf den Regeln sammeln
        foreach ($rules as $rule) {
            $identifiers = $rule->target_identifier; // Ist jetzt ein Array
            if (empty($identifiers) || !is_array($identifiers)) {
                continue;
            }

            switch ($rule->target_type) {
                case 'role':
                    $usersWithRole = User::role($identifiers)->get();
                    $recipients = $recipients->merge($usersWithRole);
                    break;

                case 'permission':
                    $usersWithPermission = User::permission($identifiers)->get();
                    $recipients = $recipients->merge($usersWithPermission);
                    break;

                case 'user':
                    if (in_array('triggering_user', $identifiers)) {
                        if ($event->triggeringUser instanceof User) {
                            $recipients->push($event->triggeringUser);
                        }
                    }
                    $userIds = collect($identifiers)->filter(fn ($id) => is_numeric($id))->all();
                    if (!empty($userIds)) {
                        $users = User::whereIn('id', $userIds)->get();
                        $recipients = $recipients->merge($users);
                    }
                    break;
            }
        }

        // Empfänger-Filterung (Akteur ausschließen, außer explizit genannt oder = TriggeringUser)
        $actorUserId = $event->actorUser ? $event->actorUser->id : null;
        $triggeringUserId = ($event->triggeringUser instanceof User) ? $event->triggeringUser->id : null;

        $notifyActorUserRuleExists = $rules->contains(function ($rule) use ($actorUserId) {
            return $rule->target_type === 'user' && is_array($rule->target_identifier) && in_array((string)$actorUserId, $rule->target_identifier, true); // Strikter Vergleich mit String-Casting
        });

        $uniqueRecipients = $recipients->unique('id');

        // Schließe Akteur aus, WENN:
        // 1. Es eine Akteur-ID gibt UND
        // 2. KEINE Regel explizit den Akteur als Ziel hat UND
        // 3. Der Akteur NICHT derselbe ist wie der Auslöser (z.B. bei Anträgen)
        if ($actorUserId && !$notifyActorUserRuleExists && $actorUserId !== $triggeringUserId) {
            $uniqueRecipients = $uniqueRecipients->reject(fn ($user) => $user->id === $actorUserId);
        }


        if ($uniqueRecipients->isEmpty()) {
            // Log::info("[Notify] Keine Empfänger (nach Filterung) für {$event->controllerAction}.");
            return;
        }

        // --- Standard Benachrichtigungsdetails ---
        $actorName = $event->actorUser ? $event->actorUser->name : 'System';
        $notificationText = "Aktion ({$event->controllerAction}) ausgeführt von {$actorName}."; // Fallback-Text
        $notificationIcon = 'fas fa-info-circle text-info'; // Fallback-Icon
        $notificationUrl = route('dashboard'); // Fallback-URL

        // --- Spezifische Logik pro Controller-Aktion ---

        // A) Antrag eingereicht (EvaluationController@store)
        if ($event->controllerAction === 'EvaluationController@store' && $event->relatedModel instanceof Evaluation) {
             /** @var Evaluation $evaluation */ $evaluation = $event->relatedModel;
             $moduleName = $evaluation->json_data['module_name'] ?? '?';
             $antragArt = ($evaluation->evaluation_type === 'modul_anmeldung') ? 'Modulanmeldung' : 'Prüfungsanmeldung';
             /** @var User $antragsteller */ $antragsteller = $event->triggeringUser; // Ist hier der User, der den Antrag stellt

             $notificationText = "Neuer Antrag ({$antragArt}) für '{$moduleName}' von {$antragsteller->name}.";
             $notificationIcon = 'fas fa-file-signature text-warning';
             // Link zur Admin-Ansicht des Antrags, Fallback auf Index
             $notificationUrl = Route::has('admin.forms.evaluations.show')
                 ? route('admin.forms.evaluations.show', $evaluation->id)
                 : route('forms.evaluations.index');
              // Weiter zu Notification::send am Ende
        }

        // B) Benutzer Modul zugewiesen (TrainingAssignmentController@assign)
        elseif ($event->controllerAction === 'TrainingAssignmentController@assign' && $event->relatedModel instanceof TrainingModule) {
             /** @var TrainingModule $module */ $module = $event->relatedModel;
             /** @var User $assignedUser */ $assignedUser = $event->triggeringUser; // Der zugewiesene User
             /** @var User $assigningAdmin */ $assigningAdmin = $event->actorUser; // Der Admin

             // Benachrichtige NUR den zugewiesenen Benutzer, falls eine Regel dafür existiert
             $notifyAssignedUserRuleExists = $rules->contains(fn($r) => $r->target_type === 'user' && is_array($r->target_identifier) && in_array($assignedUser->id, $r->target_identifier));
             if($notifyAssignedUserRuleExists) {
                 $nTextUser = "Du wurdest von {$assigningAdmin->name} dem Modul '{$module->name}' zugewiesen.";
                 $nIconUser = 'fas fa-user-graduate text-success';
                 $nUrlUser = route('modules.show', $module->id); // Link zum Modul
                 Notification::send($assignedUser, new GeneralNotification($nTextUser, $nIconUser, $nUrlUser));
             }

             // Benachrichtige andere (Admins etc.), die in den Regeln stehen, ABER NICHT der zugewiesene User sind
             $otherRecipients = $uniqueRecipients->reject(fn($u) => $u->id === $assignedUser->id);
             if($otherRecipients->isNotEmpty()){
                 $nTextAdmin = "{$assignedUser->name} wurde von {$assigningAdmin->name} dem Modul '{$module->name}' zugewiesen.";
                 $nIconAdmin = 'fas fa-user-check text-info';
                 $nUrlAdmin = route('admin.users.show', $assignedUser->id); // Link zum User-Profil
                 Notification::send($otherRecipients, new GeneralNotification($nTextAdmin, $nIconAdmin, $nUrlAdmin));
             }
            return; // Beende hier, da Benachrichtigungen spezifisch gesendet wurden
        }

        // C-E) Ankündigungen (AnnouncementController)
        elseif ($event->controllerAction === 'AnnouncementController@store' && $event->relatedModel instanceof Announcement) {
             /** @var Announcement $announcement */ $announcement = $event->relatedModel; $ersteller = $event->actorUser;
             $notificationText = "Neue Ankündigung '{$announcement->title}' wurde von {$ersteller->name} veröffentlicht.";
             $notificationIcon = 'fas fa-bullhorn text-primary'; $notificationUrl = route('dashboard'); // Oder Link zur Ankündigung?
        }
        elseif ($event->controllerAction === 'AnnouncementController@update' && $event->relatedModel instanceof Announcement) {
             /** @var Announcement $announcement */ $announcement = $event->relatedModel; $editor = $event->actorUser;
             $notificationText = "Ankündigung '{$announcement->title}' wurde von {$editor->name} bearbeitet.";
             $notificationIcon = 'fas fa-edit text-info'; $notificationUrl = route('dashboard');
        }
        elseif ($event->controllerAction === 'AnnouncementController@destroy') {
             // relatedModel ist hier das Objekt mit alten Daten oder additionalData nutzen
             $title = $event->additionalData['title'] ?? ($event->relatedModel->title ?? 'Unbekannt'); $deleter = $event->actorUser;
             $notificationText = "Ankündigung '{$title}' wurde von {$deleter->name} gelöscht.";
             $notificationIcon = 'fas fa-trash-alt text-danger'; $notificationUrl = route('admin.announcements.index');
        }

        // F-H) Admin Prüfungs-VORLAGEN Verwaltung (Admin\ExamController)
        elseif ($event->controllerAction === 'Admin\ExamController@store' && $event->relatedModel instanceof Exam) {
             /** @var Exam $exam */ $exam = $event->relatedModel; $creator = $event->actorUser;
             $notificationText = "Neue Prüfungsvorlage '{$exam->title}' wurde von {$creator->name} erstellt.";
             $notificationIcon = 'fas fa-file-medical-alt text-success'; $notificationUrl = route('admin.exams.show', $exam); // RMB nutzen
        }
        elseif ($event->controllerAction === 'Admin\ExamController@update' && $event->relatedModel instanceof Exam) {
              /** @var Exam $exam */ $exam = $event->relatedModel; $editor = $event->actorUser;
              $notificationText = "Prüfungsvorlage '{$exam->title}' wurde von {$editor->name} bearbeitet.";
              $notificationIcon = 'fas fa-edit text-info'; $notificationUrl = route('admin.exams.show', $exam); // RMB nutzen
        }
        elseif ($event->controllerAction === 'Admin\ExamController@destroy') {
             $title = $event->additionalData['title'] ?? ($event->relatedModel->title ?? 'Unbekannt'); $deleter = $event->actorUser;
             $notificationText = "Prüfungsvorlage '{$title}' wurde von {$deleter->name} gelöscht.";
             $notificationIcon = 'fas fa-trash-alt text-danger'; $notificationUrl = route('admin.exams.index');
        }

        // I-M) Admin Prüfungs-VERSUCH Verwaltung (Admin\ExamAttemptController)
        elseif ($event->controllerAction === 'Admin\ExamAttemptController@store' && $event->relatedModel instanceof ExamAttempt) { // war ExamController@generateLink / Admin\ExamController@generateLink
             /** @var ExamAttempt $attempt */ $attempt = $event->relatedModel;
             /** @var User $user */ $user = $event->triggeringUser; // Der User, FÜR den der Link ist (aus Event übergeben)
             /** @var User $admin */ $admin = $event->actorUser; // Der Admin, der den Link generiert

             // Benachrichtige den User, für den der Link ist (falls Regel existiert)
             $notifyUserRuleExists = $rules->contains(fn($r) => $r->target_type === 'user' && is_array($r->target_identifier) && in_array($user->id, $r->target_identifier));
             if ($notifyUserRuleExists) {
                 $nTextUser = "Ein Prüfungslink für '{$attempt->exam->title}' wurde von {$admin->name} für dich generiert.";
                 Notification::send($user, new GeneralNotification($nTextUser, 'fas fa-link text-info', route('exams.take', $attempt))); // RMB nutzen
             }
             // Benachrichtige andere (Admins etc.)
             $otherRecipients = $uniqueRecipients->reject(fn($u) => $u->id === $user->id);
             if ($otherRecipients->isNotEmpty()) {
                 $nTextAdmin = "Prüfungslink für '{$attempt->exam->title}' wurde von {$admin->name} für {$user->name} generiert.";
                 Notification::send($otherRecipients, new GeneralNotification($nTextAdmin, 'fas fa-link text-secondary', route('admin.exams.attempts.index')));
             }
             return; // Beende hier
        }
        elseif ($event->controllerAction === 'Admin\ExamAttemptController@update' && $event->relatedModel instanceof ExamAttempt) { // war ExamController@finalizeEvaluation
              /** @var ExamAttempt $attempt */ $attempt = $event->relatedModel;
              /** @var User $student */ $student = $attempt->user; // Student aus dem Versuch holen
              /** @var User $admin */ $admin = $event->actorUser;
              $resultText = $event->additionalData['status_result'] ?? 'unbekannt'; // 'bestanden' / 'nicht_bestanden'
              $isPassed = ($resultText === 'bestanden');
              $resultIcon = $isPassed ? 'fas fa-check-circle text-success' : 'fas fa-times-circle text-danger';

              // Benachrichtige den Schüler (falls Regel existiert)
              $notifyStudentRuleExists = $rules->contains(fn($r) => $r->target_type === 'user' && is_array($r->target_identifier) && in_array($student->id, $r->target_identifier));
              if ($notifyStudentRuleExists) {
                   $nTextStudent = "Deine Prüfung '{$attempt->exam->title}' wurde von {$admin->name} final bewertet: {$resultText} ({$attempt->score}%).";
                   Notification::send($student, new GeneralNotification($nTextStudent, $resultIcon, route('profile.show'))); // Link zum Profil
              }
              // Benachrichtige andere Admins
              $otherRecipients = $uniqueRecipients->reject(fn($u) => $u->id === $student->id);
              if ($otherRecipients->isNotEmpty()) {
                   $nTextAdmin = "Prüfung '{$attempt->exam->title}' von {$student->name} wurde von {$admin->name} final als '{$resultText}' bewertet ({$attempt->score}%).";
                   Notification::send($otherRecipients, new GeneralNotification($nTextAdmin, 'fas fa-clipboard-check text-info', route('admin.exams.attempts.show', $attempt))); // Link zur Admin-Detailansicht
              }
              return; // Beende hier
        }
        elseif ($event->controllerAction === 'Admin\ExamAttemptController@resetAttempt' && $event->relatedModel instanceof ExamAttempt) { // war Admin\ExamController@resetAttempt
             /** @var ExamAttempt $attempt */ $attempt = $event->relatedModel;
             /** @var User $resetter */ $resetter = $event->actorUser;
             /** @var User $student */ $student = $attempt->user; // Student aus dem Versuch holen

             // Benachrichtige den Schüler (falls Regel existiert)
             $notifyStudentRuleExists = $rules->contains(fn($r) => $r->target_type === 'user' && is_array($r->target_identifier) && in_array($student->id, $r->target_identifier));
             if($notifyStudentRuleExists) {
                $nTextStudent = "Dein Prüfungsversuch für '{$attempt->exam->title}' wurde von {$resetter->name} zurückgesetzt. Du kannst die Prüfung erneut ablegen.";
                $nIconStudent = 'fas fa-exclamation-triangle text-warning';
                $nUrlStudent = route('exams.take', $attempt); // Link zum erneuten Ablegen
                Notification::send($student, new GeneralNotification($nTextStudent, $nIconStudent, $nUrlStudent));
             }

             // Benachrichtige andere Admins/Prüfer
             $otherRecipients = $uniqueRecipients->reject(fn($u) => $u->id === $student->id);
             if($otherRecipients->isNotEmpty()){
                 $nTextAdmin = "Prüfungsversuch für '{$attempt->exam->title}' von {$student->name} wurde von {$resetter->name} zurückgesetzt.";
                 $nIconAdmin = 'fas fa-undo text-warning';
                 $nUrlAdmin = route('admin.exams.attempts.show', $attempt); // Link zur Admin-Detailansicht
                 Notification::send($otherRecipients, new GeneralNotification($nTextAdmin, $nIconAdmin, $nUrlAdmin));
             }
            return; // Beende hier
        }
        elseif ($event->controllerAction === 'Admin\ExamAttemptController@setEvaluated' && $event->relatedModel instanceof ExamAttempt) { // war Admin\ExamController@setEvaluated (Schnellbewertung)
             /** @var ExamAttempt $attempt */ $attempt = $event->relatedModel;
             /** @var User $evaluator */ $evaluator = $event->actorUser;
             /** @var User $student */ $student = $attempt->user; // Student aus dem Versuch holen
             $isPassed = $attempt->score >= $attempt->exam->pass_mark;
             $resultText = $isPassed ? 'bestanden' : 'nicht bestanden';
             $resultIcon = $isPassed ? 'fas fa-check-circle text-success' : 'fas fa-times-circle text-danger';

             // Benachrichtige den Schüler (falls Regel existiert)
             $notifyStudentRuleExists = $rules->contains(fn($r) => $r->target_type === 'user' && is_array($r->target_identifier) && in_array($student->id, $r->target_identifier));
             if($notifyStudentRuleExists){
                 $nTextStudent = "Deine Prüfung '{$attempt->exam->title}' wurde von {$evaluator->name} schnell-bewertet: Score {$attempt->score}%.";
                 Notification::send($student, new GeneralNotification($nTextStudent, 'fas fa-info-circle text-info', route('profile.show')));
             }

             // Benachrichtige andere Admins/Prüfer
             $otherRecipients = $uniqueRecipients->reject(fn($u) => $u->id === $student->id);
             if($otherRecipients->isNotEmpty()){
                 $nTextAdmin = "Prüfung '{$attempt->exam->title}' von {$student->name} wurde von {$evaluator->name} schnell-bewertet (Score: {$attempt->score}%).";
                 Notification::send($otherRecipients, new GeneralNotification($nTextAdmin, 'fas fa-clipboard-check text-info', route('admin.exams.attempts.show', $attempt)));
             }
             return; // Beende hier
        }
        elseif ($event->controllerAction === 'Admin\ExamAttemptController@sendLink' && $event->relatedModel instanceof ExamAttempt) { // war Admin\ExamController@sendLink
             /** @var ExamAttempt $attempt */ $attempt = $event->relatedModel;
             /** @var User $user */ $user = $attempt->user; // Der User, FÜR den der Link ist
             /** @var User $admin */ $admin = $event->actorUser; // Der Admin, der den Link generiert

             // Benachrichtige den User, für den der Link ist (falls Regel existiert)
             $notifyUserRuleExists = $rules->contains(fn($r) => $r->target_type === 'user' && is_array($r->target_identifier) && in_array($user->id, $r->target_identifier));
             if($notifyUserRuleExists) {
                 $nTextUser = "Ein neuer Prüfungslink für '{$attempt->exam->title}' wurde von {$admin->name} für dich generiert.";
                 Notification::send($user, new GeneralNotification($nTextUser, 'fas fa-link text-info', route('exams.take', $attempt))); // RMB nutzen
             }

              // Benachrichtige andere (Admins etc.)
             $otherRecipients = $uniqueRecipients->reject(fn($u) => $u->id === $user->id);
             if($otherRecipients->isNotEmpty()){
                 $nTextAdmin = "Neuer Prüfungslink für '{$attempt->exam->title}' wurde von {$admin->name} für {$user->name} generiert.";
                 Notification::send($otherRecipients, new GeneralNotification($nTextAdmin, 'fas fa-link text-secondary', route('admin.exams.attempts.show', $attempt)));
             }
             return; // Beende hier
        }
         // NEU: Löschen eines Versuchs (Admin\ExamAttemptController@destroy)
         elseif ($event->controllerAction === 'Admin\ExamAttemptController@destroy') {
             // relatedModel ist hier das Objekt mit alten Daten
             $attemptId = $event->additionalData['id'] ?? ($event->relatedModel->id ?? 'Unbekannt');
             $examTitle = $event->additionalData['exam_title'] ?? ($event->relatedModel->exam->title ?? 'Unbekannte Prüfung');
             $studentName = $event->additionalData['user_name'] ?? ($event->relatedModel->user->name ?? 'Unbekannter User');
             /** @var User $deleter */ $deleter = $event->actorUser;
             $notificationText = "Prüfungsversuch #{$attemptId} ('{$examTitle}') von {$studentName} wurde von {$deleter->name} endgültig gelöscht.";
             $notificationIcon = 'fas fa-trash-alt text-danger'; $notificationUrl = route('admin.exams.attempts.index');
             // Weiter zu Notification::send am Ende (nur Admins benachrichtigen)
         }

        // N-O) User Prüfungs-VERSUCH Aktionen (ExamAttemptController)
        elseif ($event->controllerAction === 'ExamAttemptController@update' && $event->relatedModel instanceof ExamAttempt) { // war ExamController@submit
              /** @var ExamAttempt $attempt */ $attempt = $event->relatedModel;
              /** @var User $user */ $user = $event->triggeringUser; // Ist hier der Actor UND der Triggering User

              // Benachrichtige Admins/Prüfer (alle Empfänger laut Regeln)
              if ($uniqueRecipients->isNotEmpty()) {
                   $nText = "{$user->name} hat Prüfung '{$attempt->exam->title}' eingereicht (Auto-Score: {$attempt->score}%).";
                   Notification::send($uniqueRecipients, new GeneralNotification($nText, 'fas fa-file-alt text-warning', route('admin.exams.attempts.show', $attempt))); // Link zur Admin-Ansicht
              }
              // Optional: Benachrichtige den User selbst (falls Regel existiert)
              // $notifyUserRuleExists = $rules->contains(fn($r) => ...); if($notifyUserRuleExists) { ... }
              return; // Beende hier
        }

        // P-Q) Berechtigungen & Rollen (Admin)
        elseif ($event->controllerAction === 'Admin\PermissionController@store' && $event->relatedModel instanceof Permission) {
             /** @var Permission $permission */ $permission = $event->relatedModel; $creator = $event->actorUser;
             $notificationText = "Neue Berechtigung '{$permission->name}' wurde von {$creator->name} erstellt.";
             $notificationIcon = 'fas fa-key text-success'; $notificationUrl = route('admin.permissions.index');
        }
        elseif ($event->controllerAction === 'Admin\PermissionController@update' && $event->relatedModel instanceof Permission) {
             /** @var Permission $permission */ $permission = $event->relatedModel; $editor = $event->actorUser;
             $notificationText = "Berechtigung '{$permission->name}' wurde von {$editor->name} bearbeitet.";
             $notificationIcon = 'fas fa-edit text-info'; $notificationUrl = route('admin.permissions.index');
        }
        elseif ($event->controllerAction === 'Admin\PermissionController@destroy') {
             $name = $event->additionalData['name'] ?? ($event->relatedModel->name ?? 'Unbekannt'); $deleter = $event->actorUser;
             $notificationText = "Berechtigung '{$name}' wurde von {$deleter->name} gelöscht.";
             $notificationIcon = 'fas fa-trash-alt text-danger'; $notificationUrl = route('admin.permissions.index');
        }
        elseif ($event->controllerAction === 'Admin\RoleController@store' && $event->relatedModel instanceof Role) {
             /** @var Role $role */ $role = $event->relatedModel; $creator = $event->actorUser;
             $notificationText = "Neue Rolle '{$role->name}' wurde von {$creator->name} erstellt.";
             $notificationIcon = 'fas fa-user-shield text-success'; $notificationUrl = route('admin.roles.index'); // Link zur Übersicht
        }
        elseif ($event->controllerAction === 'Admin\RoleController@update' && $event->relatedModel instanceof Role) {
             /** @var Role $role */ $role = $event->relatedModel; $editor = $event->actorUser;
             $notificationText = "Rolle '{$role->name}' wurde von {$editor->name} bearbeitet.";
             $notificationIcon = 'fas fa-edit text-info'; $notificationUrl = route('admin.roles.index'); // Link zur Übersicht
        }
        elseif ($event->controllerAction === 'Admin\RoleController@destroy') {
             $name = $event->additionalData['name'] ?? ($event->relatedModel->name ?? 'Unbekannt'); $deleter = $event->actorUser;
             $notificationText = "Rolle '{$name}' wurde von {$deleter->name} gelöscht.";
             $notificationIcon = 'fas fa-trash-alt text-danger'; $notificationUrl = route('admin.roles.index');
        }

        // R-T) Benutzerverwaltung & Akteneinträge (Admin)
        elseif ($event->controllerAction === 'Admin\UserController@store' && $event->relatedModel instanceof User) {
             /** @var User $newUser */ $newUser = $event->relatedModel; $creator = $event->actorUser;
             $notificationText = "Neuer Benutzer '{$newUser->name}' wurde von {$creator->name} angelegt.";
             $notificationIcon = 'fas fa-user-plus text-success'; $notificationUrl = route('admin.users.show', $newUser); // RMB nutzen
        }
        elseif ($event->controllerAction === 'Admin\UserController@update' && $event->relatedModel instanceof User) {
             /** @var User $editedUser */ $editedUser = $event->relatedModel; $editor = $event->actorUser;
             $notificationText = "Profil von '{$editedUser->name}' wurde von {$editor->name} bearbeitet.";
             // Prüfen, ob Module geändert wurden (aus additionalData)
             $addedModules = $event->additionalData['added_modules'] ?? [];
             $removedModules = $event->additionalData['removed_modules'] ?? [];
             if(!empty($addedModules) || !empty($removedModules)){
                 $notificationText .= " Modulzuweisungen wurden angepasst.";
                 $notificationIcon = 'fas fa-user-graduate text-info';
             } else {
                 $notificationIcon = 'fas fa-user-edit text-info';
             }
             $notificationUrl = route('admin.users.show', $editedUser); // RMB nutzen
        }
        elseif ($event->controllerAction === 'Admin\UserController@addRecord' && $event->relatedModel instanceof ServiceRecord) {
             /** @var ServiceRecord $record */ $record = $event->relatedModel;
             /** @var User $targetUser */ $targetUser = $event->triggeringUser; // Der User, der den Eintrag erhält
             /** @var User $author */ $author = $event->actorUser;
             $notificationText = "Neuer Akteneintrag ('{$record->type}') wurde von {$author->name} für {$targetUser->name} hinzugefügt.";
             $notificationIcon = 'fas fa-folder-plus text-primary'; $notificationUrl = route('admin.users.show', $targetUser); // RMB nutzen
        }

        // U-W) Patientenakten (CitizenController)
        elseif ($event->controllerAction === 'CitizenController@store' && $event->relatedModel instanceof Citizen) {
             /** @var Citizen $citizen */ $citizen = $event->relatedModel; $creator = $event->actorUser;
             $notificationText = "Neue Patientenakte für '{$citizen->name}' wurde von {$creator->name} erstellt.";
             $notificationIcon = 'fas fa-address-book text-success'; $notificationUrl = route('citizens.show', $citizen); // RMB nutzen
        }
        elseif ($event->controllerAction === 'CitizenController@update' && $event->relatedModel instanceof Citizen) {
              /** @var Citizen $citizen */ $citizen = $event->relatedModel; $editor = $event->actorUser;
              $notificationText = "Patientenakte von '{$citizen->name}' wurde von {$editor->name} bearbeitet.";
              $notificationIcon = 'fas fa-edit text-info'; $notificationUrl = route('citizens.show', $citizen); // RMB nutzen
        }
        elseif ($event->controllerAction === 'CitizenController@destroy') {
             $name = $event->additionalData['name'] ?? ($event->relatedModel->name ?? 'Unbekannt'); $deleter = $event->actorUser;
             $notificationText = "Patientenakte von '{$name}' wurde von {$deleter->name} gelöscht.";
             $notificationIcon = 'fas fa-trash-alt text-danger'; $notificationUrl = route('citizens.index');
        }

        // X-Y) Dienststatus (DutyStatusController)
        elseif ($event->controllerAction === 'DutyStatusController@toggle.on_duty' && $event->triggeringUser instanceof User) {
             /** @var User $user */ $user = $event->triggeringUser;
             $notificationText = "{$user->name} hat den Dienst angetreten.";
             $notificationIcon = 'fas fa-user-clock text-success'; $notificationUrl = route('admin.users.show', $user); // RMB nutzen
        }
        elseif ($event->controllerAction === 'DutyStatusController@toggle.off_duty' && $event->triggeringUser instanceof User) {
             /** @var User $user */ $user = $event->triggeringUser;
             $notificationText = "{$user->name} hat den Dienst beendet.";
             $notificationIcon = 'fas fa-user-clock text-danger'; $notificationUrl = route('admin.users.show', $user); // RMB nutzen
        }

        // CC-DD) Rezeptverwaltung (PrescriptionController)
        elseif ($event->controllerAction === 'PrescriptionController@store' && $event->relatedModel instanceof Prescription) {
              /** @var Prescription $prescription */ $prescription = $event->relatedModel;
              /** @var Citizen|null $citizen */ $citizen = $event->triggeringUser; // Triggering ist hier der Citizen oder null
              /** @var User $doctor */ $doctor = $event->actorUser;
              $citizenName = ($citizen instanceof Citizen) ? $citizen->name : 'Unbekannt';
              $notificationText = "Neues Rezept ('{$prescription->medication}') wurde von {$doctor->name} für {$citizenName} ausgestellt.";
              $notificationIcon = 'fas fa-prescription-bottle-alt text-success';
              $notificationUrl = ($citizen instanceof Citizen) ? route('citizens.show', [$citizen, 'tab' => 'prescriptions']) : route('citizens.index');
        }
        elseif ($event->controllerAction === 'PrescriptionController@destroy') {
              $medication = $event->additionalData['name'] ?? ($event->relatedModel->medication ?? 'Unbekannt');
              $citizenName = $event->additionalData['citizen_name'] ?? 'Unbekannt';
              /** @var User $doctor */ $doctor = $event->actorUser;
              $notificationText = "Rezept ('{$medication}') für {$citizenName} wurde von {$doctor->name} storniert.";
              $notificationIcon = 'fas fa-trash-alt text-danger';
              $citizenId = $event->additionalData['citizen_id'] ?? ($event->relatedModel->citizen_id ?? null);
              $notificationUrl = $citizenId ? route('citizens.show', [$citizenId, 'tab' => 'prescriptions']) : route('citizens.index');
        }

        // EE-GG) Einsatzberichte (ReportController)
        elseif ($event->controllerAction === 'ReportController@store' && $event->relatedModel instanceof Report) {
            /** @var Report $report */ $report = $event->relatedModel; $creator = $event->actorUser;
            $patientName = $report->patient_name ?? 'Unbekannt';
            $notificationText = "Neuer Einsatzbericht ('{$report->title}') wurde von {$creator->name} erstellt (Patient: {$patientName}).";
            $notificationIcon = 'fas fa-file-medical text-success'; $notificationUrl = route('reports.show', $report); // RMB
        }
        elseif ($event->controllerAction === 'ReportController@update' && $event->relatedModel instanceof Report) {
            /** @var Report $report */ $report = $event->relatedModel; $editor = $event->actorUser;
            $notificationText = "Einsatzbericht '{$report->title}' wurde von {$editor->name} bearbeitet.";
            $notificationIcon = 'fas fa-edit text-info'; $notificationUrl = route('reports.show', $report); // RMB
        }
        elseif ($event->controllerAction === 'ReportController@destroy') {
            $title = $event->additionalData['title'] ?? ($event->relatedModel->title ?? 'Unbekannt');
            $patientName = $event->additionalData['patient_name'] ?? ($event->relatedModel->patient_name ?? 'Unbekannt');
            $deleter = $event->actorUser;
            $notificationText = "Einsatzbericht '{$title}' (Patient: {$patientName}) wurde von {$deleter->name} gelöscht.";
            $notificationIcon = 'fas fa-trash-alt text-danger'; $notificationUrl = route('reports.index');
        }

        // HH-KK) Ausbildungsmodule (TrainingModuleController)
        elseif ($event->controllerAction === 'TrainingModuleController@store' && $event->relatedModel instanceof TrainingModule) {
             /** @var TrainingModule $module */ $module = $event->relatedModel; $creator = $event->actorUser;
             $notificationText = "Neues Ausbildungsmodul '{$module->name}' wurde von {$creator->name} erstellt.";
             $notificationIcon = 'fas fa-graduation-cap text-success'; $notificationUrl = route('modules.show', $module); // RMB
        }
        elseif ($event->controllerAction === 'TrainingModuleController@update' && $event->relatedModel instanceof TrainingModule) {
             /** @var TrainingModule $module */ $module = $event->relatedModel; $editor = $event->actorUser;
             $notificationText = "Ausbildungsmodul '{$module->name}' wurde von {$editor->name} bearbeitet.";
             $notificationIcon = 'fas fa-edit text-info'; $notificationUrl = route('modules.show', $module); // RMB
        }
        elseif ($event->controllerAction === 'TrainingModuleController@destroy') {
             $name = $event->additionalData['name'] ?? ($event->relatedModel->name ?? 'Unbekannt'); $deleter = $event->actorUser;
             $notificationText = "Ausbildungsmodul '{$name}' wurde von {$deleter->name} gelöscht.";
             $notificationIcon = 'fas fa-trash-alt text-danger'; $notificationUrl = route('modules.index');
        }
        // elseif ($event->controllerAction === 'TrainingModuleController@signUp' ... ) {
        //     // Diese Aktion erstellt jetzt eine Evaluation, daher greift Block A)
        // }

        // MM-NN) Urlaubsanträge (VacationController)
        elseif ($event->controllerAction === 'VacationController@store' && $event->relatedModel instanceof Vacation) {
             /** @var Vacation $vacation */ $vacation = $event->relatedModel; $requester = $event->triggeringUser;
             $notificationText = "Neuer Urlaubsantrag von {$requester->name} ({$vacation->start_date->format('d.m.Y')} - {$vacation->end_date->format('d.m.Y')}).";
             $notificationIcon = 'fas fa-plane-departure text-warning'; $notificationUrl = route('admin.vacations.index');
        }
        elseif ($event->controllerAction === 'VacationController@updateStatus' && $event->relatedModel instanceof Vacation) {
             /** @var Vacation $vacation */ $vacation = $event->relatedModel;
             /** @var User $user */ $user = $event->triggeringUser; // Der User, dessen Antrag bearbeitet wurde
             /** @var User $admin */ $admin = $event->actorUser; // Der Admin
             $status = $event->additionalData['status'] ?? 'unbekannt'; // 'approved' / 'rejected'
             $statusText = $status === 'approved' ? 'genehmigt' : 'abgelehnt';
             $statusIcon = $status === 'approved' ? 'fas fa-check-circle text-success' : 'fas fa-times-circle text-danger';

             // Benachrichtige den Antragsteller (falls Regel existiert)
             $notifyUserRuleExists = $rules->contains(fn($r) => $r->target_type === 'user' && is_array($r->target_identifier) && in_array($user->id, $r->target_identifier));
             if ($notifyUserRuleExists) {
                  $nTextU = "Dein Urlaubsantrag ({$vacation->start_date->format('d.m.Y')} - {$vacation->end_date->format('d.m.Y')}) wurde von {$admin->name} {$statusText}.";
                  Notification::send($user, new GeneralNotification($nTextU, $statusIcon, route('profile.show'))); // Link zum eigenen Profil
             }

             // Benachrichtige andere Admins etc.
             $otherRecipients = $uniqueRecipients->reject(fn($u) => $u->id === $user->id);
             if ($otherRecipients->isNotEmpty()) {
                  $nTextA = "Urlaubsantrag von {$user->name} wurde von {$admin->name} {$statusText}.";
                  Notification::send($otherRecipients, new GeneralNotification($nTextA, 'fas fa-calendar-check text-info', route('admin.vacations.index')));
             }
             return; // Beende hier
        }


        // --- Fallback oder gemeinsamer Sendeaufruf für die Fälle oben, die kein 'return;' hatten ---
        if ($uniqueRecipients->isNotEmpty()) {
            // Log::info("[Notify] Sende Standard-Benachrichtigung für {$event->controllerAction} an {$uniqueRecipients->count()} Empfänger: {$notificationText}");
            Notification::send($uniqueRecipients, new GeneralNotification($notificationText, $notificationIcon, $notificationUrl));
        } else {
            // Log::warning("[Notify] Keine Empfänger nach Filterung für {$event->controllerAction} gefunden.");
        }
    }
}
