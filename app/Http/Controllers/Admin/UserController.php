<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ServiceRecord;
use App\Models\Evaluation;
use App\Models\ExamAttempt;
use App\Models\TrainingModule; // Import hinzugefügt
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Validation\Rule;
use App\Models\ActivityLog;
use App\Events\PotentiallyNotifiableActionOccurred; // Event hinzufügen

class UserController extends Controller
{
    /**
     * Definiert die Hierarchie der Ränge als private Eigenschaft.
     * @var array
     */
    private $rankHierarchy = [
        'ems-director'           => 8,
        'assistant-ems-director' => 7,
        'instructor'             => 6,
        'emergency-doctor'       => 5,
        'paramedic'              => 4,
        'emt'                    => 3,
        'emt-trainee'            => 2,
        'praktikant'             => 1,
    ];

    /**
     * Definiert Abteilungen und deren Rollen-Hierarchie.
     * @var array
     */
    private $departmentalRoles = [
        'Rechtsabteilung' => [
            'leitung_role' => 'rechtsabteilung - leitung',
            'min_rank_to_assign_leitung' => 7, // Assistant EMS Director
            'roles' => [
                'Rechtsabteilung - leitung',
                'Rechtsabteilung - mitglied',
            ],
        ],
        'Ausbildungsabteilung' => [
            'leitung_role' => 'ausbildungsabteilung - leitung',
            'min_rank_to_assign_leitung' => 7, // Assistant EMS Director
            'roles' => [
                'Ausbildungsabteilung - leitung',
                'Ausbildungsabteilung - ausbilder',
                'Ausbildungsabteilung - ausbilder auf probe',
            ],
        ],
        'Personalabteilung' => [
            'leitung_role' => 'personalabteilung - leitung',
            'min_rank_to_assign_leitung' => 7, // Assistant EMS Director
            'roles' => [
                'Personalabteilung - leitung',
                'Personalabteilung - mitglied',
            ],
        ],
    ];

    /**
     * Definiert die unsichtbare Super-Admin Rolle.
     * @var string
     */
    private $superAdminRole = 'Super-Admin';


    public function __construct()
    {
        $this->middleware('can:users.view')->only('index', 'show'); // 'show' für die Admin-Ansicht hinzufügen
        $this->middleware('can:users.create')->only(['create', 'store']);
        $this->middleware('can:users.edit')->only(['edit', 'update']);
        $this->middleware('can:users.manage.record')->only('addRecord');
        // NEU: Zusätzliche Berechtigung für Modul-Management
        $this->middleware('can:users.manage.modules')->only(['update']); // Nur beim Speichern prüfen
    }

    /**
     * Gibt eine gefilterte Liste der Rollen zurück, die der aktuelle Admin verwalten darf.
     */
    private function getManagableRoles()
    {
        $admin = Auth::user();

        // Ausnahme: Director und Super-Admin dürfen immer alle Rollen verwalten (außer Super-Admin selbst).
        if ($admin->hasAnyRole('ems-director', $this->superAdminRole)) {
            return Role::where('name', '!=', $this->superAdminRole)->get();
        }

        $adminRankLevel = $admin->getHighestRankLevel();
        $adminRoleNames = $admin->getRoleNames();

        $allRoles = Role::where('name', '!=', $this->superAdminRole)->get(); // Super-Admin ausschließen
        $managableRoles = collect();

        foreach ($allRoles as $role) {
            // 1. Rang-Rollen prüfen
            if (isset($this->rankHierarchy[$role->name])) {
                if ($this->rankHierarchy[$role->name] < $adminRankLevel) {
                    $managableRoles->push($role);
                }
                continue;
            }

            // 2. Abteilungs-Rollen prüfen
            foreach ($this->departmentalRoles as $department) {
                if (in_array($role->name, $department['roles'])) {
                    if ($role->name === $department['leitung_role']) {
                        if ($adminRankLevel >= $department['min_rank_to_assign_leitung']) {
                            $managableRoles->push($role);
                        }
                    } else {
                        if ($adminRoleNames->contains($department['leitung_role'])) {
                            $managableRoles->push($role);
                        }
                    }
                    break;
                }
            }
        }

        return $managableRoles->unique('id');
    }

    /**
     * NEU: Hilfsfunktion, die die Super-Admin Rolle aus der Anzeige entfernt.
     */
    private function filterSuperAdminFromRoles(User $user): User
    {
        if ($user->relationLoaded('roles')) {
            $filteredRoles = $user->roles->reject(function ($role) {
                return $role->name === $this->superAdminRole;
            });
            $user->setRelation('roles', $filteredRoles);
        }
        return $user;
    }

    public function index()
    {
        $users = User::with('roles')->orderBy('personal_number')->get();

        // KORREKTUR: Filtere die Super-Admin-Rolle für die Anzeige heraus.
        $users->each(function ($user) {
            $this->filterSuperAdminFromRoles($user);
        });

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $roles = $this->getManagableRoles();
        $statuses = [
            'Aktiv', 'Probezeit', 'Beobachtung', 'Beurlaubt', 'Krankgeschrieben',
            'Suspendiert', 'Ausgetreten', 'Bewerbungsphase',
        ];
        return view('admin.users.create', compact('roles', 'statuses'));
    }

    public function store(Request $request)
    {
        $managableRoleNames = $this->getManagableRoles()->pluck('name')->toArray();

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'cfx_id' => 'required|string|unique:users,cfx_id',
            'status' => 'required|string',
            'roles' => 'sometimes|array',
            'roles.*' => [Rule::in($managableRoleNames)],
            // Weitere optionale Felder
             'email' => 'nullable|email|max:255',
             'birthday' => 'nullable|date',
             'discord_name' => 'nullable|string|max:255',
             'forum_name' => 'nullable|string|max:255',
             'hire_date' => 'nullable|date', // Wird jetzt ggf. überschrieben
        ]);

        $selectedRoles = $request->roles ?? [];
        $highestRankName = 'praktikant';
        $highestLevel = 0;

        foreach ($selectedRoles as $roleName) {
            if (isset($this->rankHierarchy[$roleName]) && $this->rankHierarchy[$roleName] > $highestLevel) {
                $highestLevel = $this->rankHierarchy[$roleName];
                $highestRankName = $roleName;
            }
        }
        $validatedData['rank'] = $highestRankName;
        $validatedData['second_faction'] = $request->has('second_faction') ? 'Ja' : 'Nein';

        do {
            $newEmployeeId = rand(10000, 99999);
        } while (User::where('employee_id', $newEmployeeId)->exists());
        $validatedData['employee_id'] = $newEmployeeId;

        // Einstellungsdatum nur setzen, wenn es nicht explizit übergeben wurde
        if (empty($validatedData['hire_date'])) {
            $validatedData['hire_date'] = now();
        }
        $validatedData['last_edited_by'] = Auth::user()->name;
        $validatedData['last_edited_at'] = now();

        $user = User::create($validatedData);
        $user->syncRoles($selectedRoles);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'log_type' => 'USER',
            'action' => 'CREATED',
            'target_id' => $user->id,
            'description' => "Neuer Mitarbeiter '{$user->name}' ({$user->id}) angelegt.",
        ]);

        PotentiallyNotifiableActionOccurred::dispatch(
            'Admin\UserController@store',
            $user,
            $user,
            Auth::user()
        );

        return redirect()->route('admin.users.index');
    }

    /**
     * Zeigt das Profil eines spezifischen Benutzers (Admin-Ansicht).
     * Der View 'profile.show' wird hier wiederverwendet.
     */
    public function show(User $user)
    {
        // Laden der gleichen Relationen wie im ProfileController, um den View zu füllen.
        $user->load([
            // 'examinations', // Wurde entfernt
            'trainingModules',
            'vacations',
            'receivedEvaluations' => fn($q) => $q->with('evaluator')->latest(),
        ]);

        // 1. Prüfungsversuche laden
        $examAttempts = ExamAttempt::where('user_id', $user->id)
                                    ->with('exam')
                                    ->latest('completed_at') // Sortiert nach Abschlussdatum
                                    ->get();

        // 2. Weitere Variablen laden, die der View erwartet
        $serviceRecords = $user->serviceRecords()->with('author')->latest()->get();
        $evaluationCounts = $this->calculateEvaluationCounts($user);

        // Annahme: Diese Methoden existieren im User Model
        $hourData = $user->calculateDutyHours();
        $weeklyHours = $user->calculateWeeklyHoursSinceEntry();

        // Filter Super-Admin Rolle
        $this->filterSuperAdminFromRoles($user);

        // $examinations ist nicht mehr nötig
        return view('profile.show', compact(
            'user',
            'serviceRecords',
            'examAttempts', // Übergeben
            'evaluationCounts',
            'hourData',
            'weeklyHours'
            // 'examinations' => collect() // Leere Collection übergeben oder komplett entfernen
        ));
    }

    private function calculateEvaluationCounts(User $user): array
    {
         $typeLabels = ['azubi', 'praktikant', 'mitarbeiter', 'leitstelle']; // Nur relevante Typen
         $counts = ['verfasst' => [], 'erhalten' => []];

         // Zählungen des Profilbesitzers ($user) - ERHALTEN
         $receivedCounts = Evaluation::selectRaw('evaluation_type, count(*) as count')
                                     ->where('user_id', $user->id)
                                     ->whereIn('evaluation_type', $typeLabels)
                                     ->groupBy('evaluation_type')
                                     ->pluck('count', 'evaluation_type');

         // Zählungen des angemeldeten Benutzers (Auth::user()) - VERFASST
         // Wichtig: Zeigt *immer* die vom *aktuell eingeloggten Admin* verfassten an, nicht die vom Profilinhaber!
         $authoredCounts = Evaluation::selectRaw('evaluation_type, count(*) as count')
                                     ->where('evaluator_id', Auth::id())
                                     ->whereIn('evaluation_type', $typeLabels)
                                     ->groupBy('evaluation_type')
                                     ->pluck('count', 'evaluation_type');

         // Initialisiere mit 0 und fülle die Ergebnisse auf
         foreach ($typeLabels as $type) {
             $counts['erhalten'][$type] = $receivedCounts->get($type, 0);
             $counts['verfasst'][$type] = $authoredCounts->get($type, 0);
         }

         return $counts;
    }

    public function edit(User $user)
    {
        $statuses = [
            'Aktiv', 'Beurlaubt', 'Beobachtung', 'Krankgeschrieben',
            'Suspendiert', 'Ausgetreten', 'Bewerbungsphase', 'Probezeit',
        ];
        $roles = $this->getManagableRoles();
        $permissions = Permission::all()->sortBy('name')->groupBy(function ($item) {
            // Updated grouping logic to handle permissions without '-'
            $parts = explode('.', $item->name, 2); // Split by '.'
            return $parts[0];
        });
        $userDirectPermissions = $user->getPermissionNames()->toArray();

        $allPossibleNumbers = range(1, 150);
        // Schließe nur Nummern von AKTIVEN Usern aus (außer dem aktuellen User selbst)
        $takenNumbers = User::where('status', 'Aktiv')->where('id', '!=', $user->id)->pluck('personal_number')->toArray();
        $availablePersonalNumbers = array_diff($allPossibleNumbers, $takenNumbers);

        // Alle verfügbaren Trainingsmodule laden
        $allModules = TrainingModule::orderBy('category')->orderBy('name')->get();
        // Die IDs der Module laden, die der User bereits hat
        $userModules = $user->trainingModules()->pluck('training_module_id')->toArray();

        return view('admin.users.edit', compact(
            'user',
            'roles',
            'permissions',
            'userDirectPermissions',
            'availablePersonalNumbers',
            'statuses',
            'allModules',     // <-- NEU übergeben
            'userModules'     // <-- NEU übergeben
        ));
    }

    public function update(Request $request, User $user)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'roles' => 'sometimes|array',
            'permissions' => 'sometimes|array',
            'status' => 'required|string',
            'personal_number' => ['required', 'integer', 'min:1', 'max:150', Rule::unique('users')->ignore($user->id)],
            'employee_id' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'birthday' => 'nullable|date',
            'discord_name' => 'nullable|string|max:255',
            'forum_name' => 'nullable|string|max:255',
            'special_functions' => 'nullable|string',
             'hire_date' => 'nullable|date', // Einstellungsdatum validieren

            // NEU: Validierung für Module
            'modules' => 'sometimes|array',
            'modules.*' => 'exists:training_modules,id',
        ]);

        $managableRoleNames = $this->getManagableRoles()->pluck('name')->toArray();
        $originalRoleNames = $user->getRoleNames()->toArray();
        $submittedRoleNames = $request->input('roles', []);

        // Prüfen, ob der Admin versucht, Rollen zuzuweisen, die er nicht managen darf
        $newlyAddedRoles = array_diff($submittedRoleNames, $originalRoleNames);
        foreach ($newlyAddedRoles as $addedRole) {
            if (!in_array($addedRole, $managableRoleNames)) {
                return redirect()->back()
                                ->withErrors(['roles' => 'Sie haben nicht die Berechtigung, die Rolle "' . $addedRole . '" zuzuweisen.'])
                                ->withInput();
            }
        }
        // Sicherstellen, dass die Super-Admin-Rolle nicht entfernt werden kann
        if ($user->hasRole($this->superAdminRole)) {
            if (!in_array($this->superAdminRole, $submittedRoleNames)) {
                $submittedRoleNames[] = $this->superAdminRole;
            }
        }

        $validatedData['second_faction'] = $request->has('second_faction') ? 'Ja' : 'Nein';

        $oldRank = $user->rank;
        $oldStatus = $user->status;
        $newStatus = $validatedData['status'];

        // Einstellungsdatum neu setzen, wenn von inaktiv zu aktiv gewechselt wird
        $inactiveStatuses = ['Ausgetreten', 'inaktiv', 'Suspendiert']; // 'inaktiv' hinzugefügt falls verwendet
        $activeStatuses = ['Aktiv', 'Probezeit', 'Bewerbungsphase']; // Ggf. anpassen
        if (in_array($oldStatus, $inactiveStatuses) && in_array($newStatus, $activeStatuses)) {
            // Nur setzen, wenn hire_date nicht explizit im Formular gesetzt wurde
            if (empty($validatedData['hire_date'])) {
                 $validatedData['hire_date'] = now();
            }
        }

        // Höchsten Rang neu berechnen
        $newRank = 'praktikant'; // Standard
        $highestLevel = 0;
        foreach ($submittedRoleNames as $roleName) {
            if (isset($this->rankHierarchy[$roleName]) && $this->rankHierarchy[$roleName] > $highestLevel) {
                $highestLevel = $this->rankHierarchy[$roleName];
                $newRank = $roleName;
            }
        }
        $validatedData['rank'] = $newRank;

        // Clone User object BEFORE update
        $userBeforeUpdate = clone $user;
        // Lade die Module VOR dem Update, um Änderungen zu erkennen
        $userBeforeUpdate->load('trainingModules');
        $oldModuleIds = $userBeforeUpdate->trainingModules->pluck('id')->toArray();

        // Rollen und Berechtigungen synchronisieren
        $user->syncRoles($submittedRoleNames);
        $user->syncPermissions($request->permissions ?? []);

        // Stammdaten aktualisieren
        $validatedData['last_edited_at'] = now();
        $validatedData['last_edited_by'] = Auth::user()->name;
        $user->update($validatedData);

        // --- Module synchronisieren ---
        $submittedModuleIds = $request->input('modules', []);
        $modulesToSync = [];
        $adminName = Auth::user()->name;
        $timestamp = now();

        foreach ($submittedModuleIds as $moduleId) {
            // Prüfen, ob der User das Modul bereits hat, um Daten nicht zu überschreiben
            $existingPivot = $userBeforeUpdate->trainingModules->find($moduleId)?->pivot;

            if ($existingPivot) {
                // Wenn Modul schon vorhanden war, behalte bestehende Daten bei,
                // außer es wird explizit als 'bestanden' markiert (was hier der Fall ist).
                 $modulesToSync[$moduleId] = [
                    'status' => 'bestanden', // Status auf 'bestanden' setzen/überschreiben
                    'completed_at' => $existingPivot->completed_at ?? $timestamp->toDateString(), // Bestehendes Datum behalten, sonst neues
                    'notes' => $existingPivot->notes // Bestehende Notizen behalten
                        . "\nManuell geprüft/bestätigt von {$adminName} am " . $timestamp->format('d.m.Y H:i') // Vermerk hinzufügen
                 ];
            } else {
                // Standard-Pivot-Daten für NEU manuell zugewiesene Module
                $modulesToSync[$moduleId] = [
                    'status' => 'bestanden',
                    'completed_at' => $timestamp->toDateString(),
                    'notes' => "Manuell zugewiesen von {$adminName} am " . $timestamp->format('d.m.Y H:i')
                ];
            }
        }
        $user->trainingModules()->sync($modulesToSync);
        // --- Ende Modul-Synchronisation ---

        // Reload relationships to reflect changes for the event/log
        $user->load(['roles', 'trainingModules']);
        $newModuleIds = $user->trainingModules->pluck('id')->toArray();

        // Activity Log erstellen
        $description = "Benutzerprofil von '{$user->name}' ({$user->id}) aktualisiert.";
        if ($oldRank !== $newRank) {
            $description .= " Rang geändert: {$oldRank} -> {$newRank}.";
        }
        if ($oldStatus !== $validatedData['status']) {
            $description .= " Status geändert: {$oldStatus} -> {$validatedData['status']}.";
        }

        // Log für Moduländerungen
        $addedModules = array_diff($newModuleIds, $oldModuleIds);
        $removedModules = array_diff($oldModuleIds, $newModuleIds);
        if (!empty($addedModules)) {
            $addedModuleNames = TrainingModule::whereIn('id', $addedModules)->pluck('name')->implode(', ');
            $description .= " Module manuell hinzugefügt/bestätigt: {$addedModuleNames}.";
        }
        if (!empty($removedModules)) {
            $removedModuleNames = TrainingModule::whereIn('id', $removedModules)->pluck('name')->implode(', ');
            $description .= " Module entfernt: {$removedModuleNames}.";
        }

        ActivityLog::create([
             'user_id' => Auth::id(),
             'log_type' => 'USER_RECORD', // Typ ggf. anpassen auf 'USER'
             'action' => 'UPDATED',
             'target_id' => $user->id,
             'description' => $description,
         ]);

        // Service Record bei Beförderung/Degradierung
        if ($oldRank !== $newRank) {
            $currentRankLevel = $this->rankHierarchy[$newRank] ?? 0;
            $oldRankLevel = $this->rankHierarchy[$oldRank] ?? 0;
            $recordType = $currentRankLevel > $oldRankLevel ? 'Beförderung' : ($currentRankLevel < $oldRankLevel ? 'Degradierung' : 'Rangänderung');
            ServiceRecord::create([
                'user_id' => $user->id,
                'author_id' => Auth::id(),
                'type' => $recordType,
                'content' => "Rang geändert von '{$oldRank}' zu '{$newRank}'."
            ]);
        }

        // Event auslösen
        PotentiallyNotifiableActionOccurred::dispatch(
            'Admin\UserController@update',
            $user,
            $user,
            Auth::user(),
            ['old_rank' => $oldRank, 'old_status' => $oldStatus, 'added_modules' => $addedModules, 'removed_modules' => $removedModules]
        );

        return redirect()->route('admin.users.index'); // Ohne success
    }

    public function addRecord(Request $request, User $user)
    {
        $request->validate(['type' => 'required|string', 'content' => 'required|string']);

        $record = ServiceRecord::create([
            'user_id' => $user->id, 'author_id' => Auth::id(),
            'type' => $request->type, 'content' => $request->content
        ]);

        // Update last edited info
        $user->update(['last_edited_at' => now(), 'last_edited_by' => Auth::user()->name]);

        ActivityLog::create([
            'user_id' => Auth::id(), 'log_type' => 'USER_RECORD', 'action' => 'ADDED',
            'target_id' => $user->id,
            'description' => "Eintrag (Typ: {$request->type}) zur Personalakte von '{$user->name}' hinzugefügt.",
        ]);

        PotentiallyNotifiableActionOccurred::dispatch(
            'Admin\UserController@addRecord',
            $user,
            $record,
            Auth::user()
        );

        return redirect()->route('admin.users.show', $user); // Ohne success
    }
}
