<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ServiceRecord;
use App\Models\Evaluation;
use App\Models\ExamAttempt;
use App\Models\TrainingModule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\ActivityLog;
use App\Events\PotentiallyNotifiableActionOccurred;

// --- ANGEPASSTE USE-STATEMENTS ---
use App\Models\Role; // Benutzt dein eigenes Role-Modell
use Spatie\Permission\Models\Permission;
use App\Models\Department; // NEU: Department-Modell
use App\Models\Rank;       // NEU: Rank-Modell
// ------------------------------------

use App\Models\Pivots\TrainingModuleUser;
use Carbon\Carbon; // NEU: Für Datumsvergleiche

class UserController extends Controller
{
    /**
     * Definiert die unsichtbare Super-Admin Rolle.
     * @var string
     */
    private $superAdminRole = 'Super-Admin';


    public function __construct()
    {
        $this->middleware('can:users.view')->only('index', 'show');
        $this->middleware('can:users.create')->only(['create', 'store']);
        $this->middleware('can:users.edit')->only(['edit', 'update']);
        $this->middleware('can:users.manage.record')->only('addRecord');
        $this->middleware('can:users.manage.modules')->only(['update']);
    }

    /**
     * Gibt eine gefilterte Liste der Rollen zurück, die der aktuelle Admin verwalten darf.
     * (Angepasst an die Datenbank)
     */
    private function getManagableRoles()
    {
        $admin = Auth::user();

        // 1. Lade Konfigurationen aus der DB
        $ranks = Rank::pluck('level', 'name');
        $departments = Department::with('roles')->get(); // Lädt Abteilungen & deren Rollen

        // Ausnahme: 'chief' (oder Super-Admin) dürfen immer alle Rollen verwalten (außer Super-Admin).
        if ($admin->hasAnyRole('chief', $this->superAdminRole)) {
            return Role::where('name', '!=', $this->superAdminRole)->get();
        }

        // $admin->rank ist ein STRING (z.B. 'captain'), basierend auf deiner store/update Logik
        $adminRankLevel = $ranks->get($admin->rank, 0);
        $adminRoleNames = $admin->getRoleNames();

        $allRoles = Role::where('name', '!=', $this->superAdminRole)->get(); // Super-Admin ausschließen
        $managableRoles = collect();

        foreach ($allRoles as $role) {
            // 1. Rang-Rollen prüfen
            if ($ranks->has($role->name)) {
                if ($ranks[$role->name] < $adminRankLevel) {
                    $managableRoles->push($role);
                }
                continue; 
            }

            // 2. Abteilungs-Rollen prüfen
            foreach ($departments as $department) {
                if ($department->roles->contains('name', $role->name)) {
                    // Ist es die Leitungsrolle?
                    if ($role->name === $department->leitung_role_name) {
                        if ($adminRankLevel >= $department->min_rank_level_to_assign_leitung) {
                            $managableRoles->push($role);
                        }
                    } else {
                        // Es ist eine "normale" Abteilungsrolle
                        if ($adminRoleNames->contains($department->leitung_role_name)) {
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
     * KORRIGIERT: Hilfsfunktion, die die Super-Admin Rolle aus der Anzeige entfernt.
     * Klont den User, um das Original (z.B. Auth::user()) nicht zu verändern.
     */
    private function filterSuperAdminFromRoles(User $user): User
    {
        // KORREKTUR: Klonen, um das Originalobjekt nicht zu verändern (wichtig für Auth::user())
        $viewUser = clone $user; 
        
        if ($viewUser->relationLoaded('roles')) {
            $filteredRoles = $viewUser->roles->reject(function ($role) {
                return $role->name === $this->superAdminRole;
            });
            // Modifiziere nur den Klon
            $viewUser->setRelation('roles', $filteredRoles);
        }
        // Gib den modifizierten Klon zurück
        return $viewUser;
    }

    public function index()
    {
        $users = User::with('roles')->orderBy('personal_number')->get();

        // KORREKTUR: Filtere die Super-Admin-Rolle für die Anzeige heraus.
        // Verwende map(), um eine neue Collection mit den gefilterten Klonen zu erstellen.
        $filteredUsers = $users->map(function ($user) {
            return $this->filterSuperAdminFromRoles($user);
        });

        // KORREKTUR: 'compact' kann keine assoziativen Zuweisungen ('=>') annehmen.
        // Wir übergeben das Array direkt.
        return view('admin.users.index', ['users' => $filteredUsers]);
    }

    public function create()
    {
        $managableRoles = $this->getManagableRoles(); // Die flache Liste aller Rollen
        
        // --- NEUE KATEGORISIERUNG ---
        $allRanks = Rank::pluck('name'); // Alle Rang-Namen aus der DB
        $allDepartments = Department::with('roles')->get(); // Alle Abteilungen & ihre Rollen
        
        $categorizedRoles = [
            'Ranks' => [],
            'Departments' => [],
            'Other' => []
        ];

        foreach ($managableRoles as $role) {
            // 1. Ist es ein Rang?
            if ($allRanks->contains($role->name)) {
                $categorizedRoles['Ranks'][] = $role;
                continue;
            }
            
            // 2. Ist es eine Abteilungsrolle?
            $found = false;
            foreach ($allDepartments as $dept) {
                if ($dept->roles->contains('id', $role->id)) {
                    // Erstelle die Abteilungskategorie, falls sie noch nicht existiert
                    if (!isset($categorizedRoles['Departments'][$dept->name])) {
                        $categorizedRoles['Departments'][$dept->name] = [];
                    }
                    $categorizedRoles['Departments'][$dept->name][] = $role;
                    $found = true;
                    break; // Rolle gefunden, nächste Rolle prüfen
                }
            }

            // 3. Wenn nirgends zugeordnet -> "Andere"
            if (!$found) {
                $categorizedRoles['Other'][] = $role;
            }
        }
        // --- ENDE KATEGORISIERUNG ---

        $statuses = [
            'Aktiv', 'Probezeit', 'Beobachtung', 'Beurlaubt', 'Krankgeschrieben',
            'Suspendiert', 'Ausgetreten', 'Bewerbungsphase',
        ];
        
        // WICHTIG: Wir übergeben $categorizedRoles statt $roles
        return view('admin.users.create', compact('categorizedRoles', 'statuses'));
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
            'email' => 'nullable|email|max:255',
            'birthday' => 'nullable|date',
            'discord_name' => 'nullable|string|max:255',
            'forum_name' => 'nullable|string|max:255',
            'hire_date' => 'nullable|date',
        ]);

        $selectedRoles = $request->roles ?? [];
        
        // --- ANGEPASSTE RANG-LOGIK (DB-ABFRAGE) ---
        $highestRankName = 'praktikant'; // Dein Standardwert
        $highestLevel = 0;
        
        // Hole die Level der Ränge, die auch ausgewählt wurden, aus der DB
        $rankLevels = Rank::whereIn('name', $selectedRoles)->pluck('level', 'name');

        foreach ($selectedRoles as $roleName) {
            if ($rankLevels->has($roleName) && $rankLevels[$roleName] > $highestLevel) {
                $highestLevel = $rankLevels[$roleName];
                $highestRankName = $roleName;
            }
        }
        $validatedData['rank'] = $highestRankName;
        // --- ENDE ANGEPASSTE RANG-LOGIK ---

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
       // KORREKTUR: Laden der Relationen, 'roles' hinzugefügt
        $user->load([
            'vacations',
            'receivedEvaluations' => fn($q) => $q->with('evaluator')->latest(),
            'roles' // KORREKTUR: Rollen müssen geladen werden!
        ]);

        // 1. Lade die Module
        $user->load('trainingModules');

        // 2. Lade die 'assigner'-Beziehung AUF die Pivot-Objekte
        if ($user->trainingModules->isNotEmpty()) {
            $pivots = $user->trainingModules->pluck('pivot'); 
            (new \Illuminate\Database\Eloquent\Collection($pivots))->load('assigner');
        }

        // 1. Prüfungsversuche laden
        $examAttempts = ExamAttempt::where('user_id', $user->id)
                                    ->with('exam.trainingModule')
                                    ->latest('completed_at') 
                                    ->get();

        // 2. Weitere Variablen laden
        $serviceRecords = $user->serviceRecords()->with('author')->latest()->get();
        $evaluationCounts = $this->calculateEvaluationCounts($user);
        $hourData = $user->calculateDutyHours();
        $weeklyHours = $user->calculateWeeklyHoursSinceEntry();

        // KORREKTUR: Wende den "sicheren" Filter an und übergib den Klon an die View
        $viewUser = $this->filterSuperAdminFromRoles($user);

        // KORREKTUR: 'compact' kann keine assoziativen Zuweisungen ('=>') annehmen.
        // Wir übergeben das Array direkt, damit die View die Variable $user erhält.
        return view('profile.show', [
            'user' => $viewUser, // KORREKTUR: Übergib den gefilterten Klon
            'serviceRecords' => $serviceRecords,
            'examAttempts' => $examAttempts,
            'evaluationCounts' => $evaluationCounts,
            'hourData' => $hourData,
            'weeklyHours' => $weeklyHours
        ]);
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
        
        // --- NEUE KATEGORISIERUNG ---
        $managableRoles = $this->getManagableRoles(); // Die flache Liste
        $allRanks = Rank::pluck('name');
        $allDepartments = Department::with('roles')->get();
        
        $categorizedRoles = [
            'Ranks' => [],
            'Departments' => [],
            'Other' => []
        ];

        foreach ($managableRoles as $role) {
            // 1. Ist es ein Rang?
            if ($allRanks->contains($role->name)) {
                $categorizedRoles['Ranks'][] = $role;
                continue;
            }
            // 2. Ist es eine Abteilungsrolle?
            $found = false;
            foreach ($allDepartments as $dept) {
                if ($dept->roles->contains('id', $role->id)) {
                    if (!isset($categorizedRoles['Departments'][$dept->name])) {
                        $categorizedRoles['Departments'][$dept->name] = [];
                    }
                    $categorizedRoles['Departments'][$dept->name][] = $role;
                    $found = true;
                    break; 
                }
            }
            // 3. "Andere"
            if (!$found) {
                $categorizedRoles['Other'][] = $role;
            }
        }
        // --- ENDE KATEGORISIERUNG ---

        $permissions = Permission::all()->sortBy('name')->groupBy(function ($item) {
            $parts = explode('.', $item->name, 2);
            return $parts[0];
        });
        $userDirectPermissions = $user->getPermissionNames()->toArray();

        $allPossibleNumbers = range(1, 150);
        $takenNumbers = User::where('status', 'Aktiv')->where('id', '!=', $user->id)->pluck('personal_number')->toArray();
        $availablePersonalNumbers = array_diff($allPossibleNumbers, $takenNumbers);

        $allModules = TrainingModule::orderBy('category')->orderBy('name')->get();
        $userModules = $user->trainingModules()->pluck('training_module_id')->toArray();

        return view('admin.users.edit', compact(
            'user',
            'categorizedRoles', // NEUE Variable
            'permissions',
            'userDirectPermissions',
            'availablePersonalNumbers',
            'statuses',
            'allModules',
            'userModules'
        ));
    }

    /**
     * KORRIGIERT: update-Methode mit detailliertem Logging
     */
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
            'hire_date' => 'nullable|date', 
            'modules' => 'sometimes|array',
            'modules.*' => 'exists:training_modules,id',
        ]);

        $adminUser = Auth::user(); 

        // --- START: ROLLEN-LOGIK ---
        $managableRoleNames = $this->getManagableRoles()->pluck('name')->toArray();
        $originalRoleNames = $user->getRoleNames()->toArray();
        $submittedRoleNames = $request->input('roles', []);

        $newlyAddedRoles = array_diff($submittedRoleNames, $originalRoleNames);
        foreach ($newlyAddedRoles as $addedRole) {
            if (!in_array($addedRole, $managableRoleNames)) {
                return redirect()->back()
                                ->withErrors(['roles' => 'Sie haben nicht die Berechtigung, die Rolle "' . $addedRole . '" zuzuweisen.'])
                                ->withInput();
            }
        }
        
        $unmanagableRolesToKeep = array_diff($originalRoleNames, $managableRoleNames);
        $finalRolesToSync = array_unique(array_merge($submittedRoleNames, $unmanagableRolesToKeep));
        // --- ENDE: ROLLEN-LOGIK ---


        // --- START: DETAILLIERTES LOGGING (Setup) ---
        $userBeforeUpdate = clone $user;
        $userBeforeUpdate->load('trainingModules', 'permissions');
        
        $oldValues = [
            'name' => $userBeforeUpdate->name,
            'status' => $userBeforeUpdate->status,
            'personal_number' => $userBeforeUpdate->personal_number,
            'employee_id' => $userBeforeUpdate->employee_id,
            'email' => $userBeforeUpdate->email,
            'birthday' => $userBeforeUpdate->birthday ? $userBeforeUpdate->birthday->format('Y-m-d') : null,
            'discord_name' => $userBeforeUpdate->discord_name,
            'forum_name' => $userBeforeUpdate->forum_name,
            'special_functions' => $userBeforeUpdate->special_functions,
            'hire_date' => $userBeforeUpdate->hire_date ? $userBeforeUpdate->hire_date->format('Y-m-d') : null,
            'second_faction' => $userBeforeUpdate->second_faction,
            'rank' => $userBeforeUpdate->rank,
        ];
        
        $oldModuleIds = $userBeforeUpdate->trainingModules->pluck('id')->toArray();
        $oldRoleNames = $originalRoleNames; // Bereits oben geholt
        $oldPermissionNames = $userBeforeUpdate->getPermissionNames()->toArray();
        // --- ENDE: DETAILLIERTES LOGGING (Setup) ---


        // --- START: UPDATE-LOGIK ---

        $validatedData['second_faction'] = $request->has('second_faction') ? 'Ja' : 'Nein';
        $newStatus = $validatedData['status']; 

        $inactiveStatuses = ['Ausgetreten', 'inaktiv', 'Suspendiert']; 
        $activeStatuses = ['Aktiv', 'Probezeit', 'Bewerbungsphase']; 
        if (in_array($oldValues['status'], $inactiveStatuses) && in_array($newStatus, $activeStatuses)) {
            if (empty($validatedData['hire_date'])) {
                 $validatedData['hire_date'] = now();
            }
        }

        // --- RANG-LOGIK ---
        $newRank = 'praktikant'; 
        $highestLevel = 0;
        $rankLevels = Rank::whereIn('name', $finalRolesToSync)->pluck('level', 'name');

        foreach ($finalRolesToSync as $roleName) {
            if ($rankLevels->has($roleName) && $rankLevels[$roleName] > $highestLevel) {
                $highestLevel = $rankLevels[$roleName];
                $newRank = $roleName;
            }
        }
        $validatedData['rank'] = $newRank;
        // --- ENDE RANG-LOGIK ---

        // Hole neue Werte für den Vergleich
        $newValues = $validatedData;
        if (isset($newValues['birthday'])) {
            $newValues['birthday'] = $newValues['birthday'] ? Carbon::parse($newValues['birthday'])->format('Y-m-d') : null;
        }
        if (isset($newValues['hire_date'])) {
             $newValues['hire_date'] = $newValues['hire_date'] ? Carbon::parse($newValues['hire_date'])->format('Y-m-d') : null;
        }

        // Aktionen durchführen
        $submittedPermissionNames = $request->permissions ?? [];
        $user->syncRoles($finalRolesToSync); 
        $user->syncPermissions($submittedPermissionNames);

        $validatedData['last_edited_at'] = now();
        $validatedData['last_edited_by'] = $adminUser->name;
        $user->update($validatedData);

        // --- Module synchronisieren ---
        $submittedModuleIds = $request->input('modules', []);
        $modulesToSync = [];
        $adminName = $adminUser->name;
        $timestamp = now();

        foreach ($submittedModuleIds as $moduleId) {
            $existingPivot = $userBeforeUpdate->trainingModules->firstWhere('id', $moduleId)?->pivot;

            if ($existingPivot) {
                $modulesToSync[$moduleId] = [
                    'assigned_by_user_id' => $existingPivot->assigned_by_user_id ?? $adminUser->id,
                    'completed_at' => $existingPivot->completed_at, 
                    'notes' => $existingPivot->notes, 
                    'updated_at' => $timestamp,
                ];
            } else {
                $modulesToSync[$moduleId] = [
                    'assigned_by_user_id' => $adminUser->id,
                    'completed_at' => $timestamp->toDateString(),
                    'notes' => "Manuell zugewiesen von {$adminName} am " . $timestamp->format('d.m.Y H:i')
                ];
            }
        }
        $user->trainingModules()->sync($modulesToSync);
        // --- Ende Modul-Synchronisation ---

        // --- ENDE: UPDATE-LOGIK ---


        // --- START: DETAILLIERTES LOGGING (Generation) ---
        $user->load(['roles', 'trainingModules']);
        
        $changes = [];

        // 1. Vergleiche Stammdaten
        $fieldsToCompare = [
            'name' => 'Name', 'status' => 'Status', 'personal_number' => 'Dienstnummer',
            'employee_id' => 'Mitarbeiter-ID', 'email' => 'E-Mail', 'birthday' => 'Geburtstag',
            'discord_name' => 'Discord', 'forum_name' => 'Forum', 'special_functions' => 'Sonderfunktionen',
            'hire_date' => 'Einstelldatum', 'second_faction' => 'Zweitfraktion', 'rank' => 'Rang'
        ];

        foreach ($fieldsToCompare as $key => $label) {
            $newValue = $newValues[$key] ?? null; 
            $oldValue = $oldValues[$key] ?? null;

            if ($key === 'hire_date' && in_array($oldValues['status'], $inactiveStatuses) && in_array($newStatus, $activeStatuses) && empty($request->hire_date)) {
                 $newValue = $validatedData['hire_date']->format('Y-m-d');
            }

            if ($newValue != $oldValue) {
                $changes[] = "{$label} geändert: '{$oldValue}' -> '{$newValue}'";
            }
        }

        // 2. Vergleiche Rollen
        $addedRoles = array_diff($finalRolesToSync, $oldRoleNames);
        $removedRoles = array_diff($oldRoleNames, $finalRolesToSync);
        if (!empty($addedRoles)) {
            $changes[] = "Rollen hinzugefügt: " . implode(', ', $addedRoles);
        }
        if (!empty($removedRoles)) {
            $removedRoles = array_filter($removedRoles, fn($role) => $role !== $this->superAdminRole);
            if(!empty($removedRoles)) {
                $changes[] = "Rollen entfernt: " . implode(', ', $removedRoles);
            }
        }

        // 3. Vergleiche Berechtigungen
        $addedPermissions = array_diff($submittedPermissionNames, $oldPermissionNames);
        $removedPermissions = array_diff($oldPermissionNames, $submittedPermissionNames);
        if (!empty($addedPermissions)) {
            $changes[] = "Berechtigungen hinzugefügt: " . implode(', ', $addedPermissions);
        }
        if (!empty($removedPermissions)) {
            $changes[] = "Berechtigungen entfernt: " . implode(', ', $removedPermissions);
        }

        // 4. Vergleiche Module
        $addedModules = array_diff($submittedModuleIds, $oldModuleIds); 
        $removedModules = array_diff($oldModuleIds, $submittedModuleIds);
        if (!empty($addedModules)) {
            $addedModuleNames = TrainingModule::whereIn('id', $addedModules)->pluck('name')->implode(', ');
            $changes[] = "Module manuell hinzugefügt/bestätigt: {$addedModuleNames}";
        }
        if (!empty($removedModules)) {
            $removedModuleNames = TrainingModule::whereIn('id', $removedModules)->pluck('name')->implode(', ');
            $changes[] = "Module entfernt: {$removedModuleNames}";
        }

        // 5. Log-Beschreibung erstellen
        $description = "Benutzerprofil von '{$user->name}' ({$user->id}) aktualisiert. ";
        if (empty($changes)) {
            $description .= "Keine Änderungen vorgenommen.";
        } else {
            $description .= "Änderungen: " . implode('. ', $changes) . ".";
        }

        ActivityLog::create([
             'user_id' => Auth::id(),
             'log_type' => 'USER',
             'action' => 'UPDATED',
                 'target_id' => $user->id,
                 'description' => $description,
              ]);
        
        // --- ENDE: DETAILLIERTES LOGGING (Generation) ---

        // Service Record bei Beförderung/Degradierung
        if ($oldValues['rank'] !== $newRank) {
            $changedRankLevels = Rank::whereIn('name', [$oldValues['rank'], $newRank])
                                      ->pluck('level', 'name');
            $currentRankLevel = $changedRankLevels->get($newRank, 0);
            $oldRankLevel = $changedRankLevels->get($oldValues['rank'], 0);

            $recordType = $currentRankLevel > $oldRankLevel ? 'Beförderung' : ($currentRankLevel < $oldRankLevel ? 'Degradierung' : 'Rangänderung');
            ServiceRecord::create([
                'user_id' => $user->id,
                'author_id' => Auth::id(),
                'type' => $recordType,
                'content' => "Rang geändert von '{$oldValues['rank']}' zu '{$newRank}'."
            ]);
        }

        // Event auslösen
        PotentiallyNotifiableActionOccurred::dispatch(
            'Admin\UserController@update',
            $user,
            $user,
            Auth::user(),
            [
                // KORREKTUR: Die formatierte Beschreibung wird nun auch an das Event übergeben.
                'description' => $description, 

                // Die Rohdaten bleiben für eine detaillierte Verarbeitung erhalten
                'old_values' => $oldValues, 'added_roles' => $addedRoles, 'removed_roles' => $removedRoles,
                'added_modules' => $addedModules, 'removed_modules' => $removedModules,
                'added_permissions' => $addedPermissions, 'removed_permissions' => $removedPermissions
            ]
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

