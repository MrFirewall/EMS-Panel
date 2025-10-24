<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationRule;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Validation\Rule;
use App\Policies\NotificationRulePolicy; // Policy importieren

class NotificationRuleController extends Controller
{
    /**
     * Wendet Middleware an, um den Zugriff basierend auf Berechtigungen zu steuern.
     */
    public function __construct()
    {
        // Nutze Policy-Gates oder spezifische Permissions
        $this->middleware('can:viewAny,' . NotificationRule::class)->only('index');
        $this->middleware('can:create,' . NotificationRule::class)->only(['create', 'store']);
        // 'edit' und 'update' werden durch die Policy in der Methode geprüft
        // 'destroy' wird durch die Policy in der Methode geprüft
    }

    /**
     * Zeigt die Liste der Benachrichtigungsregeln an.
     */
    public function index()
    {
        // $this->authorize('viewAny', NotificationRule::class); // Bereits durch Middleware abgedeckt
        $rules = NotificationRule::latest()->paginate(25); // Paginierung für die Ansicht
        return view('admin.notification-rules.index', compact('rules'));
    }

    /**
     * Zeigt das Formular zum Erstellen einer neuen Regel an.
     */
    public function create()
    {
        // $this->authorize('create', NotificationRule::class); // Bereits durch Middleware abgedeckt
        $controllerActions = $this->getAvailableControllerActions();
        $targetTypes = $this->getTargetTypes();
        $availableIdentifiers = $this->getAvailableIdentifiers();

        return view('admin.notification-rules.create', compact('controllerActions', 'targetTypes', 'availableIdentifiers'));
    }

    /**
     * Speichert eine neue Benachrichtigungsregel.
     */
    public function store(Request $request)
    {
        // $this->authorize('create', NotificationRule::class); // Bereits durch Middleware abgedeckt
        $validated = $this->validateRule($request);
        $validated['is_active'] = $request->has('is_active');

        NotificationRule::create($validated);

        // Optional: Erfolgsmeldung hinzufügen
        // return redirect()->route('admin.notification-rules.index')->with('success', 'Benachrichtigungsregel erfolgreich erstellt.');
        return redirect()->route('admin.notification-rules.index');
    }

    /**
     * Zeigt das Formular zum Bearbeiten einer Regel an.
     */
    public function edit(NotificationRule $notificationRule)
    {
        $this->authorize('update', $notificationRule); // Policy-Prüfung hier
        $controllerActions = $this->getAvailableControllerActions();
        $targetTypes = $this->getTargetTypes();
        $availableIdentifiers = $this->getAvailableIdentifiers();

        return view('admin.notification-rules.edit', compact('notificationRule', 'controllerActions', 'targetTypes', 'availableIdentifiers'));
    }


    /**
     * Aktualisiert eine bestehende Benachrichtigungsregel.
     */
    public function update(Request $request, NotificationRule $notificationRule)
    {
        $this->authorize('update', $notificationRule); // Policy-Prüfung hier
        $validated = $this->validateRule($request, $notificationRule);
        $validated['is_active'] = $request->has('is_active');

        $notificationRule->update($validated);

        // Optional: Erfolgsmeldung hinzufügen
        // return redirect()->route('admin.notification-rules.index')->with('success', 'Benachrichtigungsregel erfolgreich aktualisiert.');
        return redirect()->route('admin.notification-rules.index');
    }

    /**
     * Löscht eine Benachrichtigungsregel.
     */
    public function destroy(NotificationRule $notificationRule)
    {
        $this->authorize('delete', $notificationRule); // Policy-Prüfung hier
        $notificationRule->delete();

        // Optional: Erfolgsmeldung hinzufügen
        // return redirect()->route('admin.notification-rules.index')->with('success', 'Benachrichtigungsregel erfolgreich gelöscht.');
        return redirect()->route('admin.notification-rules.index');
    }

    /**
     * Validiert die Eingaben für eine Regel.
     */
    private function validateRule(Request $request, ?NotificationRule $rule = null): array
    {
        $availableActions = array_keys($this->getAvailableControllerActions());
        $availableTypes = array_keys($this->getTargetTypes());

        return $request->validate([
            // 'controller_action' ist ein Array von Strings
            'controller_action' => ['required', 'array', 'min:1'],
            'controller_action.*' => ['required', 'string', Rule::in($availableActions)],

            // 'target_type' bleibt ein einzelner String
            'target_type' => ['required', 'string', Rule::in($availableTypes)],

            // 'target_identifier' ist ein Array von Strings
            'target_identifier' => ['required', 'array', 'min:1'],
            'target_identifier.*' => ['required', 'string', 'max:255'], // Identifier können auch 'triggering_user' sein

            'event_description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable'], // Wird als boolean behandelt (vorhanden = true)
        ]);
    }


    /**
     * Gibt die verfügbaren Controller-Aktionen zurück (aktualisiert).
     */
    private function getAvailableControllerActions(): array
    {
        // Schlüssel ist der String, der im Event übergeben wird
        // Wert ist die Beschreibung für das Dropdown im Frontend
        return [
            // Evaluations / Anträge
            'EvaluationController@store' => 'Antrag eingereicht (Modul/Prüfung)',

            // Modulzuweisung
            'TrainingAssignmentController@assign' => '[Admin] Benutzer Modul zugewiesen',

            // Ankündigungen
            'AnnouncementController@store' => 'Neue Ankündigung erstellt',
            'AnnouncementController@update' => 'Ankündigung aktualisiert',
            'AnnouncementController@destroy' => 'Ankündigung gelöscht',

            // Admin Exam VORLAGEN Management
            'Admin\ExamController@store' => '[Admin] Neue Prüfungsvorlage erstellt',
            'Admin\ExamController@update' => '[Admin] Prüfungsvorlage aktualisiert',
            'Admin\ExamController@destroy' => '[Admin] Prüfungsvorlage gelöscht',

            // Admin Exam VERSUCH Management <-- NEUE/GEÄNDERTE PFADE
            'Admin\ExamAttemptController@store' => '[Admin] Prüfungslink generiert (Antrag bestätigt)',
            'Admin\ExamAttemptController@update' => '[Admin] Prüfung final bewertet',
            'Admin\ExamAttemptController@resetAttempt' => '[Admin] Prüfungsversuch zurückgesetzt',
            'Admin\ExamAttemptController@setEvaluated' => '[Admin] Prüfungsversuch schnell-bewertet (manuell)',
            'Admin\ExamAttemptController@sendLink' => '[Admin] Prüfungslink erneut generiert/gesendet',
            'Admin\ExamAttemptController@destroy' => '[Admin] Prüfungsversuch gelöscht', // NEU

            // User Exam VERSUCH Aktionen <-- NEUE PFADE
            'ExamAttemptController@update' => 'Prüfung eingereicht (User)', // Früher ExamController@submit

            // Berechtigungen
            'Admin\PermissionController@store' => '[Admin] Neue Berechtigung erstellt',
            'Admin\PermissionController@update' => '[Admin] Berechtigung aktualisiert',
            'Admin\PermissionController@destroy' => '[Admin] Berechtigung gelöscht',

            // Rollen
            'Admin\RoleController@store' => '[Admin] Neue Rolle erstellt',
            'Admin\RoleController@update' => '[Admin] Rolle aktualisiert',
            'Admin\RoleController@destroy' => '[Admin] Rolle gelöscht',

            // Benutzerverwaltung
            'Admin\UserController@store' => '[Admin] Neuer Benutzer erstellt',
            'Admin\UserController@update' => '[Admin] Benutzerprofil aktualisiert',
            'Admin\UserController@addRecord' => '[Admin] Akteneintrag hinzugefügt',

            // Patientenakten
            'CitizenController@store' => 'Neue Patientenakte erstellt',
            'CitizenController@update' => 'Patientenakte aktualisiert',
            'CitizenController@destroy' => 'Patientenakte gelöscht',

            // Dienststatus
            'DutyStatusController@toggle.on_duty' => 'Dienst angetreten',
            'DutyStatusController@toggle.off_duty' => 'Dienst beendet',

            // Rezepte
            'PrescriptionController@store' => 'Rezept ausgestellt',
            'PrescriptionController@destroy' => 'Rezept storniert',

            // Einsatzberichte
            'ReportController@store' => 'Einsatzbericht erstellt',
            'ReportController@update' => 'Einsatzbericht aktualisiert',
            'ReportController@destroy' => 'Einsatzbericht gelöscht',

            // Ausbildungsmodule
            'TrainingModuleController@store' => 'Ausbildungsmodul erstellt',
            'TrainingModuleController@update' => 'Ausbildungsmodul aktualisiert',
            'TrainingModuleController@destroy' => 'Ausbildungsmodul gelöscht',
            // 'TrainingModuleController@signUp' => 'Benutzer hat sich für Modul angemeldet (Antrag)', // Wird durch EvaluationController@store abgedeckt

            // Urlaubsanträge
            'VacationController@store' => 'Urlaubsantrag gestellt',
            'VacationController@updateStatus' => '[Admin] Urlaubsantrag bearbeitet (Genehmigt/Abgelehnt)',
        ];
    }

    /**
     * Gibt die verfügbaren Zieltypen zurück.
     */
    private function getTargetTypes(): array
    {
        return [
            'role' => 'Rolle',
            'permission' => 'Berechtigung',
            'user' => 'Einzelner Benutzer / Spezifisch', // Zusammengefasst für die Auswahl
        ];
    }

    /**
     * Holt alle möglichen Identifier für das Dropdown im Formular.
     */
    private function getAvailableIdentifiers(): array
    {
        $roles = Role::orderBy('name')->pluck('name', 'name')->all();
        $permissions = Permission::orderBy('name')->pluck('name', 'name')->all();
        // Nur aktive User anzeigen? Ggf. anpassen: ->where('status', 'Aktiv')
        $users = User::orderBy('name')->pluck('name', 'id')->all();

        return [
            'Rollen' => $roles,
            'Berechtigungen' => $permissions,
            'Benutzer' => $users,
            'Spezifisch' => [
               'triggering_user' => 'Auslösender Benutzer (der die Aktion startet/betrifft)',
               // 'actor_user' => 'Ausführender Benutzer (Admin, der klickt)', // Weniger gebräuchlich als Ziel
            ]
        ];
    }
}
