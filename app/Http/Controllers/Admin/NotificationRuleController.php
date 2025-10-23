<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationRule;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Validation\Rule;

class NotificationRuleController extends Controller
{
    /**
     * Zeigt die Liste der Benachrichtigungsregeln an.
     */
    public function index()
    {
        $this->authorize('viewAny', NotificationRule::class);
        // KORREKTUR: Verwende paginate() statt get() für DataTables View
        $rules = NotificationRule::latest()->paginate(15); // Oder eine hohe Zahl, wenn du client-side Paging willst
        // Wenn du DataTables verwendest und *alle* Daten laden willst:
        // $rules = NotificationRule::latest()->get();
        return view('admin.notification-rules.index', compact('rules'));
    }

    /**
     * Zeigt das Formular zum Erstellen einer neuen Regel an.
     */
    public function create()
    {
        $this->authorize('create', NotificationRule::class);
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
        $this->authorize('create', NotificationRule::class);
        $validated = $this->validateRule($request);
        $validated['is_active'] = $request->has('is_active');

        NotificationRule::create($validated);

        return redirect()->route('admin.notification-rules.index');
    }

    /**
     * Zeigt das Formular zum Bearbeiten einer Regel an.
     */
    public function edit(NotificationRule $notificationRule)
    {
        $this->authorize('update', $notificationRule);
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
        $this->authorize('update', $notificationRule);
        $validated = $this->validateRule($request, $notificationRule);
        $validated['is_active'] = $request->has('is_active');

        $notificationRule->update($validated);

        return redirect()->route('admin.notification-rules.index');
    }

    /**
     * Löscht eine Benachrichtigungsregel.
     */
    public function destroy(NotificationRule $notificationRule)
    {
        $this->authorize('delete', $notificationRule);
        $notificationRule->delete();

        return redirect()->route('admin.notification-rules.index');
    }

    /**
     * Validiert die Eingaben für eine Regel.
     */
private function validateRule(Request $request, ?NotificationRule $rule = null): array
    {
        $availableActions = array_keys($this->getAvailableControllerActions());
        $availableTypes = array_keys($this->getTargetTypes());

        // GEÄNDERT: Validierung für 'controller_action' und 'target_identifier'
        return $request->validate([
            // 'controller_action' ist jetzt ein Array
            'controller_action' => ['required', 'array', 'min:1'],
            'controller_action.*' => ['required', 'string', Rule::in($availableActions)], // Validiert jeden Eintrag im Array
            
            // 'target_type' bleibt ein einzelner String
            'target_type' => ['required', 'string', Rule::in($availableTypes)],
            
            // 'target_identifier' ist jetzt ein Array
            'target_identifier' => ['required', 'array', 'min:1'],
            'target_identifier.*' => ['required', 'string', 'max:255'], // Validiert jeden Eintrag im Array

            'event_description' => ['nullable', 'string', 'max:255'], 
            'is_active' => ['nullable'],
        ]);
    }


    /**
     * Gibt die verfügbaren Controller-Aktionen zurück.
     */
    private function getAvailableControllerActions(): array
    {
        return [
            'EvaluationController@store' => 'Antrag eingereicht (Modul/Prüfung)',
            'TrainingAssignmentController@assign' => 'Benutzer Modul zugewiesen',
            'AnnouncementController@store' => 'Neue Ankündigung erstellt',
            'AnnouncementController@update' => 'Ankündigung aktualisiert',
            'AnnouncementController@destroy' => 'Ankündigung gelöscht',
            'Admin\ExamController@store' => '[Admin] Neue Prüfung erstellt',
            'Admin\ExamController@update' => '[Admin] Prüfung aktualisiert',
            'Admin\ExamController@destroy' => '[Admin] Prüfung gelöscht',
            'Admin\ExamController@resetAttempt' => '[Admin] Prüfungsversuch zurückgesetzt',
            'Admin\ExamController@setEvaluated' => '[Admin] Prüfungsversuch bewertet (manuell)',
            'Admin\ExamController@sendLink' => '[Admin] Prüfungslink generiert/gesendet (manuell)',
            'Admin\PermissionController@store' => '[Admin] Neue Berechtigung erstellt',
            'Admin\PermissionController@update' => '[Admin] Berechtigung aktualisiert',
            'Admin\PermissionController@destroy' => '[Admin] Berechtigung gelöscht',
            'Admin\RoleController@store' => '[Admin] Neue Rolle erstellt',
            'Admin\RoleController@update' => '[Admin] Rolle aktualisiert',
            'Admin\RoleController@destroy' => '[Admin] Rolle gelöscht',
            'Admin\UserController@store' => '[Admin] Neuer Benutzer erstellt',
            'Admin\UserController@update' => '[Admin] Benutzerprofil aktualisiert',
            'Admin\UserController@addRecord' => '[Admin] Akteneintrag hinzugefügt',
            'CitizenController@store' => 'Neue Patientenakte erstellt',
            'CitizenController@update' => 'Patientenakte aktualisiert',
            'CitizenController@destroy' => 'Patientenakte gelöscht',
            'DutyStatusController@toggle.on_duty' => 'Dienst angetreten',
            'DutyStatusController@toggle.off_duty' => 'Dienst beendet',
            'ExamController@generateLink' => 'Prüfungslink generiert (Antrag)',
            'ExamController@submit' => 'Prüfung eingereicht (User)',
            'ExamController@finalizeEvaluation' => 'Prüfung final bewertet (Admin)',
            'PrescriptionController@store' => 'Rezept ausgestellt',
            'PrescriptionController@destroy' => 'Rezept storniert',
            'ReportController@store' => 'Einsatzbericht erstellt',
            'ReportController@update' => 'Einsatzbericht aktualisiert',
            'ReportController@destroy' => 'Einsatzbericht gelöscht',
            'TrainingModuleController@store' => 'Ausbildungsmodul erstellt',
            'TrainingModuleController@update' => 'Ausbildungsmodul aktualisiert',
            'TrainingModuleController@destroy' => 'Ausbildungsmodul gelöscht',
            'TrainingModuleController@signUp' => 'Benutzer hat sich für Modul angemeldet (Antrag)',
            'VacationController@store' => 'Urlaubsantrag gestellt',
            'VacationController@updateStatus' => 'Urlaubsantrag bearbeitet (Genehmigt/Abgelehnt)',
            // --- Füge hier zukünftige Aktionen hinzu ---
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
            'user' => 'Einzelner Benutzer',
            // 'citizen' => 'Patient (Nur für spezifische Events relevant)',
        ];
    }

     /**
     * Holt alle möglichen Identifier für das Dropdown im Formular.
     */
    private function getAvailableIdentifiers(): array
    {
        $roles = Role::orderBy('name')->pluck('name', 'name')->all();
        $permissions = Permission::orderBy('name')->pluck('name', 'name')->all();
        $users = User::orderBy('name')->pluck('name', 'id')->all();

        return [
            'Rollen' => $roles,
            'Berechtigungen' => $permissions,
            'Benutzer' => $users,
            'Spezifisch' => [
               'triggering_user' => 'Auslösender Benutzer (falls zutreffend)',
               // 'triggering_citizen' => 'Betroffener Patient (falls zutreffend)',
            ]
        ];
    }
}

