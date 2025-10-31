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
        // Wir nutzen 'chief' als hardcodierte Top-Rolle, da die DB-Abfrage (Rank::max('level')) langsamer wäre.
        if ($admin->hasAnyRole('chief', $this->superAdminRole)) {
            return Role::where('name', '!=', $this->superAdminRole)->get();
        }

        // $admin->rank ist ein STRING (z.B. 'captain'), basierend auf deiner store/update Logik
        $adminRankLevel = $ranks->get($admin->rank, 0);
        $adminRoleNames = $admin->getRoleNames();

        $allRoles = Role::where('name', '!=', $this->superAdminRole)->get(); // Super-Admin ausschließen
        $managableRoles = collect();

        foreach ($allRoles as $role) {
            // 1. Rang-Rollen prüfen (Ist die Rolle ein Rang in der 'ranks' Tabelle?)
            if ($ranks->has($role->name)) {
                // Darf der Admin diesen Rang zuweisen? (Nur Ränge unter seinem eigenen)
                if ($ranks[$role->name] < $adminRankLevel) {
                    $managableRoles->push($role);
                }
                continue; // Rolle wurde als Rang behandelt, weiter zur nächsten Rolle
            }

            // 2. Abteilungs-Rollen prüfen
            foreach ($departments as $department) {
                // Gehört die Rolle zu dieser Abteilung? (Prüft die Pivot-Tabelle)
                if ($department->roles->contains('name', $role->name)) {
                    
                    // Ist es die Leitungsrolle dieser Abteilung?
                    if ($role->name === $department->leitung_role_name) {
                        // Darf der Admin diese Leitungsrolle zuweisen? (Prüft min_rank_level)
                        if ($adminRankLevel >= $department->min_rank_level_to_assign_leitung) {
                            $managableRoles->push($role);
                        }
                    } else {
                        // Es ist eine "normale" Abteilungsrolle (z.B. Mitglied)
                        // Darf der Admin diese zuweisen (hat er selbst die Leitungsrolle)?
                        if ($adminRoleNames->contains($department->leitung_role_name)) {
                            $managableRoles->push($role);
                        }
                    }
                    break; // Rolle wurde einer Abteilung zugeordnet, nächste Rolle prüfen
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
       // Laden der einfachen Relationen
        $user->load([
            // 'trainingModules.assigner' HIER ENTFERNEN!
            'vacations',
            'receivedEvaluations' => fn($q) => $q->with('evaluator')->latest(),
        ]);

        // 1. Lade die Module
        $user->load('trainingModules');

        // 2. Lade die 'assigner'-Beziehung AUF die Pivot-Objekte (verhindert N+1 Queries)
        if ($user->trainingModules->isNotEmpty()) {
            // 1. Hole die Sammlung der Pivot-Objekte (als normale Collection)
            $pivots = $user->trainingModules->pluck('pivot'); 
            
            // 2. Erstelle eine NEUE Eloquent Collection daraus und lade die Beziehung
            (new \Illuminate\Database\Eloquent\Collection($pivots))->load('assigner');
        }

        // 1. Prüfungsversuche laden (dein bestehender Code)
        $examAttempts = ExamAttempt::where('user_id', $user->id)
                                     ->with('exam.trainingModule')
                                     ->latest('completed_at') 
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
            // 'roles', // Alte Variable
            'categorizedRoles', // NEUE Variable
            'permissions',
            'userDirectPermissions',
            'availablePersonalNumbers',
            'statuses',
            'allModules',
            'userModules'
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

        $adminUser = Auth::user(); // Der aktuell eingeloggte Admin

        // --- START: PROBLEM-LÖSUNG ---

        // 1. Hole alle Rollen, die der Admin verwalten DARF
        $managableRoleNames = $this->getManagableRoles()->pluck('name')->toArray();
        
        // 2. Hole alle Rollen, die der User VORHER hatte
        $originalRoleNames = $user->getRoleNames()->toArray();
        
        // 3. Hole die Rollen, die vom Formular übermittelt wurden
        //    (Das sind nur die managebaren Rollen, die der Admin angehakt hat)
        $submittedRoleNames = $request->input('roles', []);

        // 4. Prüfen, ob der Admin versucht, NEUE Rollen zuzuweisen, die er nicht managen darf
        $newlyAddedRoles = array_diff($submittedRoleNames, $originalRoleNames);
        foreach ($newlyAddedRoles as $addedRole) {
            if (!in_array($addedRole, $managableRoleNames)) {
                return redirect()->back()
                                ->withErrors(['roles' => 'Sie haben nicht die Berechtigung, die Rolle "' . $addedRole . '" zuzuweisen.'])
                                ->withInput();
            }
        }
        
        // 5. Finde alle Rollen, die der User bereits hat, die der Admin aber NICHT managen darf
        //    (z.B. höhere Ränge oder die Super-Admin-Rolle).
        $unmanagableRolesToKeep = array_diff($originalRoleNames, $managableRoleNames);

        // 6. Kombiniere die vom Formular übermittelten (managebaren) Rollen
        //    mit den zu behaltenden (unmanagebaren) Rollen.
        $finalRolesToSync = array_merge($submittedRoleNames, $unmanagableRolesToKeep);
        $finalRolesToSync = array_unique($finalRolesToSync);

        // Der alte Super-Admin-Check ist jetzt überflüssig, da $unmanagableRolesToKeep
        // die Super-Admin-Rolle automatisch enthält (da sie nicht in $managableRoleNames ist).
        /* if ($user->hasRole($this->superAdminRole)) {
             if (!in_array($this->superAdminRole, $submittedRoleNames)) {
                 $submittedRoleNames[] = $this->superAdminRole;
             }
        }
        */
        
        // --- ENDE: PROBLEM-LÖSUNG ---


        $validatedData['second_faction'] = $request->has('second_faction') ? 'Ja' : 'Nein';

        $oldRank = $user->rank;
        $oldStatus = $user->status;
        $newStatus = $validatedData['status'];

        // Einstellungsdatum neu setzen... (Restliche Logik bleibt gleich)
        $inactiveStatuses = ['Ausgetreten', 'inaktiv', 'Suspendiert']; 
        $activeStatuses = ['Aktiv', 'Probezeit', 'Bewerbungsphase']; 
        if (in_array($oldStatus, $inactiveStatuses) && in_array($newStatus, $activeStatuses)) {
            if (empty($validatedData['hire_date'])) {
                 $validatedData['hire_date'] = now();
            }
        }

        // --- ANGEPASSTE RANG-LOGIK (DB-ABFRAGE) ---
        $newRank = 'praktikant'; // Standard
        $highestLevel = 0;
        
        // WICHTIG: $finalRolesToSync statt $submittedRoleNames verwenden
        $rankLevels = Rank::whereIn('name', $finalRolesToSync)->pluck('level', 'name');

        // WICHTIG: $finalRolesToSync statt $submittedRoleNames verwenden
        foreach ($finalRolesToSync as $roleName) {
            if ($rankLevels->has($roleName) && $rankLevels[$roleName] > $highestLevel) {
                $highestLevel = $rankLevels[$roleName];
                $newRank = $roleName;
            }
        }
        $validatedData['rank'] = $newRank;
        // --- ENDE ANGEPASSTE RANG-LOGIK ---

        // Clone User object BEFORE update
        $userBeforeUpdate = clone $user;
        // Lade die Module VOR dem Update, um Änderungen zu erkennen
        $userBeforeUpdate->load('trainingModules');
        $oldModuleIds = $userBeforeUpdate->trainingModules->pluck('id')->toArray();

        // Rollen und Berechtigungen synchronisieren
        // WICHTIG: $finalRolesToSync statt $submittedRoleNames verwenden
        $user->syncRoles($finalRolesToSync); 
        $user->syncPermissions($request->permissions ?? []);

        // Stammdaten aktualisieren
        $validatedData['last_edited_at'] = now();
        $validatedData['last_edited_by'] = $adminUser->name;
        $user->update($validatedData);

        // --- Module synchronisieren --- (Restliche Logik bleibt gleich)
        $submittedModuleIds = $request->input('modules', []);
        $modulesToSync = [];
        $adminName = $adminUser->name;
        $timestamp = now();

        foreach ($submittedModuleIds as $moduleId) {
            // Prüfen, ob der User das Modul bereits hat
            $existingPivot = $userBeforeUpdate->trainingModules->firstWhere('id', $moduleId)?->pivot;

            if ($existingPivot) {
                // Modul war bereits vorhanden. Bestehende Daten beibehalten.
                $modulesToSync[$moduleId] = [
                    'assigned_by_user_id' => $existingPivot->assigned_by_user_id ?? $adminUser->id,
                    'completed_at' => $existingPivot->completed_at, 
                    'notes' => $existingPivot->notes, 
                    'updated_at' => $timestamp,
                ];
            } else {
                // Standard-Pivot-Daten für NEU manuell zugewiesene Module
                $modulesToSync[$moduleId] = [
                    'assigned_by_user_id' => $adminUser->id,
                    'completed_at' => $timestamp->toDateString(), // Als bestanden markiert
                    'notes' => "Manuell zugewiesen von {$adminName} am " . $timestamp->format('d.m.Y H:i')
                ];
            }
        }
        $user->trainingModules()->sync($modulesToSync);
        // --- Ende Modul-Synchronisation ---

        // Reload relationships to reflect changes for the event/log
        $user->load(['roles', 'trainingModules']);
        $newModuleIds = $user->trainingModules->pluck('id')->toArray();

        // Activity Log erstellen (Restliche Logik bleibt gleich)
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

        // Service Record bei Beförderung/Degradierung (Restliche Logik bleibt gleich)
        if ($oldRank !== $newRank) {
            
            // --- ANGEPASSTE RANG-LEVEL LOGIK (DB-ABFRAGE) ---
            $changedRankLevels = Rank::whereIn('name', [$oldRank, $newRank])
                                      ->pluck('level', 'name');
            
            $currentRankLevel = $changedRankLevels->get($newRank, 0);
            $oldRankLevel = $changedRankLevels->get($oldRank, 0);
            // --- ENDE ANGEPASSTE LOGIK ---

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