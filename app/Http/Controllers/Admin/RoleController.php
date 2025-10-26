<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use App\Events\PotentiallyNotifiableActionOccurred;

// --- ANGEPASSTE USE-STATEMENTS ---
use App\Models\Role; // Dein eigenes Role-Modell
use App\Models\Rank;
use App\Models\Department;
// ------------------------------------

class RoleController extends Controller
{
    public function __construct()
    {
        // Middleware für Rollen bleibt
        $this->middleware('can:roles.view')->only('index');
        $this->middleware('can:roles.create')->only('store');
        $this->middleware('can:roles.edit')->only(['update', 'updateRankOrder']);
        $this->middleware('can:roles.delete')->only('destroy');

        // Middleware für Department-Aktionen (überprüft Rollen-Berechtigungen)
        $this->middleware('can:roles.create')->only('storeDepartment');
        $this->middleware('can:roles.edit')->only('updateDepartment');
        $this->middleware('can:roles.delete')->only('destroyDepartment');
    }

    /**
     * Zeigt die Rollenliste (kategorisiert) und die Bearbeitungsansicht an.
     * Übergibt alle Departments und den Typ der aktuellen Rolle
     */
    public function index(Request $request)
    {
        // 1. Alle Daten laden
        $allRoles = Role::withCount('users')->get()->keyBy(fn($role) => strtolower($role->name));
        $ranks = Rank::orderBy('level', 'desc')->get();
        $allDepartments = Department::orderBy('name')->get();

        // 2. Kategorien initialisieren
        $categorizedRoles = ['Ranks' => [], 'Departments' => [], 'Other' => []];

        // 3. Ränge zuordnen
        foreach ($ranks as $rank) {
            $rankNameLower = strtolower($rank->name);
            if ($allRoles->has($rankNameLower)) {
                $roleModel = $allRoles->pull($rankNameLower);
                $roleModel->rank_id = $rank->id;
                $categorizedRoles['Ranks'][] = $roleModel;
            }
        }

        // 4. Abteilungen zuordnen
        foreach ($allDepartments as $dept) {
            $dept->loadMissing('roles');
            $categorizedRoles['Departments'][$dept->name] = [];
            foreach ($dept->roles as $role) {
                $roleNameLower = strtolower($role->name);
                if ($allRoles->has($roleNameLower)) {
                    $roleModel = $allRoles->pull($roleNameLower);
                    $roleModel->department_id = $dept->id;
                    $categorizedRoles['Departments'][$dept->name][] = $roleModel;
                }
            }
            if (empty($categorizedRoles['Departments'][$dept->name])) {
                unset($categorizedRoles['Departments'][$dept->name]);
            }
        }

        // 5. Andere Rollen
        $categorizedRoles['Other'] = $allRoles->values();

        // 6. Rechte Spalte Logik
        $permissions = Permission::all()->sortBy('name')->groupBy(fn($item) => explode('.', $item->name, 2)[0]);
        $currentRole = null;
        $currentRolePermissions = [];
        $currentRoleType = 'other';
        $currentDepartmentId = null;

        if ($request->has('role')) {
            $currentRole = Role::findById($request->query('role'));
            if ($currentRole) {
                $currentRolePermissions = $currentRole->permissions->pluck('name')->toArray();
                $currentRoleNameLower = strtolower($currentRole->name);
                if (Rank::whereRaw('LOWER(name) = ?', [$currentRoleNameLower])->exists()) {
                    $currentRoleType = 'rank';
                } elseif ($deptRole = DB::table('department_role')->where('role_id', $currentRole->id)->first()) {
                    $currentRoleType = 'department';
                    $currentDepartmentId = $deptRole->department_id;
                }
            }
        }

        // 7. Daten an die View übergeben
        return view('admin.roles.index', compact(
            'categorizedRoles',
            'permissions',
            'currentRole',
            'currentRolePermissions',
            'allDepartments',
            'currentRoleType',
            'currentDepartmentId'
        ));
    }

    /**
     * Aktualisiert die Sortierung (level) der Ränge.
     * Wird per AJAX von der Drag-and-Drop-Liste aufgerufen.
     */
    public function updateRankOrder(Request $request)
    {
        $request->validate(['order' => 'required|array']);
        $rankIds = $request->input('order');
        $maxLevel = count($rankIds);

        try {
            DB::transaction(function () use ($rankIds, $maxLevel) {
                foreach ($rankIds as $index => $rankId) {
                    $level = $maxLevel - $index;
                    Rank::where('id', $rankId)->update(['level' => $level]);
                }
            });
            cache()->forget(config('permission.cache.key'));

            // --- LOGGING FÜR RANK ORDER UPDATE ---
            ActivityLog::create([
                'user_id' => Auth::id(),
                'log_type' => 'RANK_ORDER', // Eigener Log-Typ
                'action' => 'UPDATED',
                'target_id' => null, // Kein spezifisches Ziel
                'description' => 'Rang-Hierarchie wurde neu sortiert.',
            ]);
            // --- ENDE LOGGING ---

        } catch (\Exception $e) {
            Log::error("Fehler beim Sortieren der Ränge: " . $e->getMessage()); // Logge den Fehler serverseitig
            return response()->json(['status' => 'error', 'message' => 'Fehler beim Speichern der Hierarchie.'], 500); // Generische Fehlermeldung für den Client
        }
        return response()->json(['status' => 'success', 'message' => 'Rang-Hierarchie erfolgreich aktualisiert.']);
    }

    /**
     * Speichert eine neu erstellte Rolle (Typ: Rank, Department oder Other).
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles,name',
            'role_type' => 'required|in:rank,department,other',
            'department_id' => 'required_if:role_type,department|nullable|exists:departments,id',
        ], [
            'name.unique' => 'Dieser Rollenname ist bereits vergeben.',
            'role_type.required' => 'Bitte wählen Sie einen Rollentyp.',
            'department_id.required_if' => 'Bitte wählen Sie eine Abteilung.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator, 'createRole')
                         ->withInput()
                         ->with('open_modal', 'createRoleModal');
        }

        $roleName = strtolower(trim($request->name));
        $roleType = $request->role_type;
        $departmentId = $request->department_id;
        $role = null;

        try {
            DB::beginTransaction();

            // 1. Rolle erstellen
            $role = Role::create(['name' => $roleName]);

            // 2. Zusätzliche Aktionen
            $department = null; // Für Logging/Event
            if ($roleType === 'rank') {
                Rank::create(['name' => $roleName, 'level' => 0]); // Level 0, muss sortiert werden
            } elseif ($roleType === 'department') {
                $department = Department::find($departmentId);
                if ($department) {
                    $department->roles()->attach($role->id);
                } else {
                    throw new \Exception("Ausgewählte Abteilung nicht gefunden.");
                }
            }
            DB::commit();

            // Logging
            $logDescription = "Neue Rolle '{$role->name}' (Typ: {$roleType}) erstellt.";
            if ($roleType === 'department' && $department) {
                $logDescription .= " Abteilung: {$department->name}.";
            }
            ActivityLog::create([
                'user_id' => Auth::id(),
                'log_type' => 'ROLE',
                'action' => 'CREATED',
                'target_id' => $role->id,
                'description' => $logDescription,
            ]);

            // Event
            PotentiallyNotifiableActionOccurred::dispatch(
                action_name: 'Admin\RoleController@store',
                subject: $role,
                object: $department, // Kann null sein
                actor: Auth::user(),
                context_data: ['role_type' => $roleType]
            );

            return redirect()->route('admin.roles.index', ['role' => $role->id])->with('success', 'Rolle erfolgreich erstellt.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Fehler beim Erstellen der Rolle {$roleName}: " . $e->getMessage());
            return back()->with('error', 'Fehler beim Erstellen der Rolle: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Aktualisiert Rolle, Berechtigungen UND Typ/Department-Zugehörigkeit.
     */
    public function update(Request $request, Role $role)
    {
        if ($role->name === 'super-admin' || $role->name === 'chief') {
            return back()->with('error', 'Diese Standardrolle kann nicht geändert oder verschoben werden.');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'role_type' => 'required|in:rank,department,other',
            'department_id' => 'required_if:role_type,department|nullable|exists:departments,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

         if ($validator->fails()) {
            return redirect()->route('admin.roles.index', ['role' => $role->id])
                             ->withErrors($validator, 'updateRole')
                             ->withInput();
        }

        $oldName = $role->name;
        $oldPermissions = $role->permissions->pluck('name')->toArray();
        $newName = strtolower(trim($request->name));
        $newType = $request->role_type;
        $newDepartmentId = $request->department_id;
        $permissionsToSync = $request->permissions ?? [];

        // Alten Typ bestimmen
        $oldType = 'other';
        $oldDepartmentId = null;
        $rankEntry = Rank::whereRaw('LOWER(name) = ?', [strtolower($oldName)])->first();
        if ($rankEntry) { $oldType = 'rank'; }
        elseif ($deptRole = DB::table('department_role')->where('role_id', $role->id)->first()) {
            $oldType = 'department';
            $oldDepartmentId = $deptRole->department_id;
        }

        // Kopie der Rolle vor der Änderung für das Event
        $roleBeforeUpdate = clone $role;
        $roleBeforeUpdate->permissions = $oldPermissions;
        $roleBeforeUpdate->role_type = $oldType;
        $roleBeforeUpdate->department_id = $oldDepartmentId;


        try {
            DB::beginTransaction();

            // 1. Namen und Berechtigungen aktualisieren
            $role->update(['name' => $newName]);
            $role->syncPermissions($permissionsToSync);

            // 2. Typ-Änderungen verarbeiten
            $department = null; // Für Logging/Event
            if ($oldType !== $newType || ($newType === 'department' && $oldDepartmentId != $newDepartmentId)) {
                if ($oldType === 'rank' && $rankEntry) { $rankEntry->delete(); }
                elseif ($oldType === 'department') { DB::table('department_role')->where('role_id', $role->id)->delete(); }

                if ($newType === 'rank') {
                     Rank::firstOrCreate(['name' => $newName], ['level' => 0]);
                } elseif ($newType === 'department') {
                    $department = Department::find($newDepartmentId);
                    if ($department) { $department->roles()->attach($role->id); }
                    else { throw new \Exception("Ausgewählte Abteilung nicht gefunden."); }
                }
            }
            // Namen im Rank-Table aktualisieren, falls nötig
            if ($oldName !== $newName && ($oldType === 'rank' || $newType === 'rank')) {
                 Rank::whereRaw('LOWER(name) = ?', [strtolower($oldName)])
                     ->update(['name' => $newName]);
             }

            DB::commit();

            // Logging
            $newPermissions = $role->permissions->pluck('name')->toArray();
            $addedPermissions = array_diff($newPermissions, $oldPermissions);
            $removedPermissions = array_diff($oldPermissions, $newPermissions);

            $logDescription = "Rolle '{$oldName}' aktualisiert.";
             if ($oldName !== $newName) $logDescription .= " Neuer Name: '{$newName}'.";
             if ($oldType !== $newType) $logDescription .= " Typ geändert: {$oldType} -> {$newType}.";
             if ($newType === 'department') {
                 $currentDepartmentName = $department->name ?? Department::find($newDepartmentId)->name ?? 'Unbekannt';
                 $logDescription .= " Abteilung: {$currentDepartmentName}.";
             } elseif ($oldType === 'department' && $newType !== 'department') {
                 $oldDepartmentName = Department::find($oldDepartmentId)->name ?? 'Unbekannt (ID: ' . $oldDepartmentId . ')';
                 $logDescription .= " Aus Abteilung '{$oldDepartmentName}' entfernt.";
             }
             if (!empty($addedPermissions)) $logDescription .= " Perms hinzugefügt: " . implode(', ', $addedPermissions) . ".";
             if (!empty($removedPermissions)) $logDescription .= " Perms entfernt: " . implode(', ', $removedPermissions) . ".";

            ActivityLog::create([
                'user_id' => Auth::id(),
                'log_type' => 'ROLE',
                'action' => 'UPDATED',
                'target_id' => $role->id,
                'description' => $logDescription,
            ]);

            // Event
            PotentiallyNotifiableActionOccurred::dispatch(
                action_name: 'Admin\RoleController@update',
                subject: $role,
                object: $department ?? ($newType === 'department' ? Department::find($newDepartmentId) : null),
                actor: Auth::user(),
                context_data: [
                    'old_role_data' => $roleBeforeUpdate->toArray(),
                    'new_role_type' => $newType,
                    'new_department_id' => $newDepartmentId,
                    'added_permissions' => $addedPermissions,
                    'removed_permissions' => $removedPermissions,
                ]
            );

            return redirect()->route('admin.roles.index', ['role' => $role->id])->with('success', 'Rolle erfolgreich aktualisiert.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Fehler beim Aktualisieren der Rolle {$role->id}: " . $e->getMessage());
            return redirect()->route('admin.roles.index', ['role' => $role->id])->with('error', 'Fehler beim Aktualisieren: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Entfernt die Rolle UND ggf. den zugehörigen Rank-Eintrag.
     */
    public function destroy(Role $role)
    {
        if ($role->name === 'super-admin' || $role->name === 'chief' || $role->users()->count() > 0) {
             $error = match(true) {
                 $role->name === 'super-admin', $role->name === 'chief' => 'Standardrollen können nicht gelöscht werden.',
                 $role->users()->count() > 0 => 'Rolle kann nicht gelöscht werden, da noch Benutzer zugewiesen sind.',
                 default => 'Diese Rolle kann nicht gelöscht werden.'
             };
            return back()->with('error', $error);
        }

        $roleName = $role->name;
        $roleId = $role->id;
        $deletedRoleData = clone $role; // Komplettes Objekt klonen für Event
        $deletedRoleData->load('permissions'); // Lade Berechtigungen für das Event

        try {
            DB::beginTransaction();

            $rankEntry = Rank::whereRaw('LOWER(name) = ?', [strtolower($roleName)])->first();
            if ($rankEntry) { $rankEntry->delete(); }
            $role->delete(); // Löscht Rolle und Pivot-Einträge via Cascade (wenn Migration korrekt)

            DB::commit();

            // Logging
            ActivityLog::create([
                'user_id' => Auth::id(),
                'log_type' => 'ROLE',
                'action' => 'DELETED',
                'target_id' => $roleId,
                'description' => "Rolle '{$roleName}' gelöscht.",
            ]);

            // Event
            PotentiallyNotifiableActionOccurred::dispatch(
                action_name: 'Admin\RoleController@destroy',
                subject: $deletedRoleData,
                object: null,
                actor: Auth::user(),
                context_data: []
            );

            return redirect()->route('admin.roles.index')->with('success', "Rolle '{$roleName}' erfolgreich gelöscht.");

        } catch (\Exception $e) {
             DB::rollBack();
             Log::error("Fehler beim Löschen der Rolle {$roleId}: " . $e->getMessage());
             return back()->with('error', 'Fehler beim Löschen der Rolle.');
        }
    }

    // ===========================================
    // DEPARTMENT CRUD METHODEN
    // ===========================================

    /**
     * Speichert eine neue Abteilung.
     */
    public function storeDepartment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'department_name' => 'required|string|max:255|unique:departments,name',
             'leitung_role_name' => 'nullable|string|exists:roles,name',
             'min_rank_level_to_assign_leitung' => 'nullable|integer|min:0',
        ], [
            'department_name.required' => 'Der Abteilungsname ist erforderlich.',
            'department_name.unique' => 'Eine Abteilung mit diesem Namen existiert bereits.',
        ]);

        if ($validator->fails()) {
             return back()->withErrors($validator, 'createDepartment')
                          ->withInput()
                          ->with('open_modal', 'createDepartmentModal');
        }

        try {
            $department = Department::create([
                'name' => $request->department_name,
                 'leitung_role_name' => $request->leitung_role_name ?? '',
                 'min_rank_level_to_assign_leitung' => $request->min_rank_level_to_assign_leitung ?? 0,
            ]);

            // Logging
            ActivityLog::create([
                'user_id' => Auth::id(),
                'log_type' => 'DEPARTMENT',
                'action' => 'CREATED',
                'target_id' => $department->id,
                'description' => "Neue Abteilung '{$department->name}' erstellt.",
            ]);
            // Hier kein Event, wie vorher besprochen

            return redirect()->route('admin.roles.index')->with('success', 'Abteilung erfolgreich erstellt.');

        } catch (\Exception $e) {
             Log::error("Fehler beim Erstellen der Abteilung: " . $e->getMessage());
             return back()->with('error', 'Fehler beim Erstellen der Abteilung.')->withInput();
        }
    }

    /**
     * Aktualisiert eine Abteilung.
     */
    public function updateDepartment(Request $request, Department $department)
    {
         $validator = Validator::make($request->all(), [
             'edit_department_name' => 'required|string|max:255|unique:departments,name,' . $department->id,
             'edit_leitung_role_name' => 'nullable|string|exists:roles,name',
             'edit_min_rank_level_to_assign_leitung' => 'nullable|integer|min:0',
         ], [
             // --- HIER DIE FEHLERMELDUNGEN ---
             'edit_department_name.required' => 'Der Abteilungsname darf nicht leer sein.',
             'edit_department_name.unique' => 'Eine Abteilung mit diesem Namen existiert bereits.',
             'edit_leitung_role_name.exists' => 'Die angegebene Leitungsrolle existiert nicht.',
             'edit_min_rank_level_to_assign_leitung.integer' => 'Das minimale Rang-Level muss eine Zahl sein.',
             'edit_min_rank_level_to_assign_leitung.min' => 'Das minimale Rang-Level darf nicht negativ sein.',
             // --- ENDE FEHLERMELDUNGEN ---
         ]);

         if ($validator->fails()) {
              return back()->withErrors($validator, 'editDepartment_' . $department->id)
                           ->withInput()
                           ->with('open_modal', 'editDepartmentModal_' . $department->id);
         }

        try {
            $oldName = $department->name;
            $oldData = $department->toArray(); // Für Event

            $department->update([
                'name' => $request->edit_department_name,
                'leitung_role_name' => $request->edit_leitung_role_name ?? $department->leitung_role_name,
                'min_rank_level_to_assign_leitung' => $request->edit_min_rank_level_to_assign_leitung ?? $department->min_rank_level_to_assign_leitung,
            ]);

            // Logging
            $logDescription = "Abteilung '{$oldName}' aktualisiert.";
            if ($oldName !== $department->name) $logDescription .= " Neuer Name: '{$department->name}'.";
            // Ggf. Log für andere Felder

            ActivityLog::create([
                'user_id' => Auth::id(),
                'log_type' => 'DEPARTMENT',
                'action' => 'UPDATED',
                'target_id' => $department->id,
                'description' => $logDescription,
            ]);

            // Event
            PotentiallyNotifiableActionOccurred::dispatch(
                 action_name: 'Admin\RoleController@updateDepartment',
                 subject: $department,
                 object: null,
                 actor: Auth::user(),
                 context_data: ['old_department_data' => $oldData]
            );

            return redirect()->route('admin.roles.index')->with('success', 'Abteilung erfolgreich aktualisiert.');

        } catch (\Exception $e) {
             Log::error("Fehler beim Aktualisieren der Abteilung {$department->id}: " . $e->getMessage());
             return back()->with('error', 'Fehler beim Aktualisieren der Abteilung.')->withInput();
        }
    }

    /**
     * Löscht eine Abteilung.
     */
    public function destroyDepartment(Department $department)
    {
        if ($department->roles()->count() > 0) {
             return back()->with('error', 'Abteilung kann nicht gelöscht werden, da ihr noch Rollen zugewiesen sind.');
        }

        try {
            $deptName = $department->name;
            $deptId = $department->id;
            $deletedDeptData = $department->toArray(); // Für Event

            $department->delete();

            // Logging
            ActivityLog::create([
                'user_id' => Auth::id(),
                'log_type' => 'DEPARTMENT',
                'action' => 'DELETED',
                'target_id' => $deptId,
                'description' => "Abteilung '{$deptName}' gelöscht.",
            ]);

            // Event
            PotentiallyNotifiableActionOccurred::dispatch(
                  action_name: 'Admin\RoleController@destroyDepartment',
                  subject: (object) $deletedDeptData, // Gelöschte Daten als Objekt
                  object: null,
                  actor: Auth::user(),
                  context_data: []
            );

            return redirect()->route('admin.roles.index')->with('success', 'Abteilung erfolgreich gelöscht.');

        } catch (\Exception $e) {
             Log::error("Fehler beim Löschen der Abteilung {$department->id}: " . $e->getMessage());
             return back()->with('error', 'Fehler beim Löschen der Abteilung.');
        }
    }
}

