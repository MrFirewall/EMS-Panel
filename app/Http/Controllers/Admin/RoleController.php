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
        $this->middleware('can:roles.edit')->only(['update', 'updateRankOrder']); // updateRankOrder hinzugefügt
        $this->middleware('can:roles.delete')->only('destroy');
        
        // Middleware für Department-Aktionen (überprüft Rollen-Berechtigungen)
        // Die Routen-Definition hat dies bereits, aber zur Sicherheit hier auch
        $this->middleware('can:roles.create')->only('storeDepartment');
        $this->middleware('can:roles.edit')->only('updateDepartment');
        $this->middleware('can:roles.delete')->only('destroyDepartment');
    }

    /**
     * Zeigt die Rollenliste (kategorisiert) und die Bearbeitungsansicht an.
     * (Angepasst: Übergibt alle Departments und den Typ der aktuellen Rolle)
     */
    public function index(Request $request)
    {
        // 1. Alle Daten laden
        $allRoles = Role::withCount('users')->get()->keyBy(fn($role) => strtolower($role->name));
        $ranks = Rank::orderBy('level', 'desc')->get();
        // Lade ALLE Departments für die Dropdowns etc.
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
        // Verwende $allDepartments, die wir oben geladen haben
        foreach ($allDepartments as $dept) { 
            // Lade die Rollen-Beziehung explizit, falls nicht eager-loaded
            $dept->loadMissing('roles'); 
            $categorizedRoles['Departments'][$dept->name] = [];
            foreach ($dept->roles as $role) {
                $roleNameLower = strtolower($role->name);
                if ($allRoles->has($roleNameLower)) {
                    $roleModel = $allRoles->pull($roleNameLower);
                    // Füge Department-ID hinzu, um zu wissen, wozu es gehört
                    $roleModel->department_id = $dept->id; 
                    $categorizedRoles['Departments'][$dept->name][] = $roleModel;
                }
            }
             // Entferne leere Abteilungs-Arrays
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
        $currentRoleType = 'other'; // Standard
        $currentDepartmentId = null; // Standard

        if ($request->has('role')) {
            $currentRole = Role::findById($request->query('role')); 
            if ($currentRole) {
                $currentRolePermissions = $currentRole->permissions->pluck('name')->toArray();
                
                // Typ der aktuellen Rolle bestimmen
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
            'allDepartments', // Für Dropdowns
            'currentRoleType', // Für Bearbeitungsformular
            'currentDepartmentId' // Für Bearbeitungsformular
        ));
    }
    
    /**
     * NEUE METHODE: Aktualisiert die Sortierung (level) der Ränge.
     * Wird per AJAX von der Drag-and-Drop-Liste aufgerufen.
     */
    public function updateRankOrder(Request $request)
    {
        $request->validate(['order' => 'required|array']);

        $rankIds = $request->input('order');
        
        // Das höchste Level ist die Anzahl der Ränge
        // (z.B. 11 Ränge -> Level 11, 10, 9...)
        $maxLevel = count($rankIds); 

        try {
            DB::transaction(function () use ($rankIds, $maxLevel) {
                foreach ($rankIds as $index => $rankId) {
                    // Das erste Item (index 0) bekommt das höchste Level
                    $level = $maxLevel - $index; 
                    
                    // Wir aktualisieren den 'level' in der 'ranks'-Tabelle
                    Rank::where('id', $rankId)->update(['level' => $level]);
                }
            });

            // WICHTIG: Cache für Berechtigungen leeren, falls Ränge Berechtigungen beeinflussen
            cache()->forget(config('permission.cache.key'));

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Fehler beim Speichern der Hierarchie: ' . $e->getMessage()
            ], 500);
        }

        return response()->json([
            'status' => 'success', 
            'message' => 'Rang-Hierarchie erfolgreich aktualisiert.'
        ]);
    }

    /**
     * Speichert eine neu erstellte Rolle (Typ: Rank, Department oder Other).
     * (STARK ANGEPASST)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles,name',
            'role_type' => 'required|in:rank,department,other',
            // Department_id ist nur erforderlich, wenn role_type 'department' ist
            'department_id' => 'required_if:role_type,department|nullable|exists:departments,id', 
        ], [
            'name.unique' => 'Dieser Rollenname ist bereits vergeben.',
            'role_type.required' => 'Bitte wählen Sie einen Rollentyp.',
            'department_id.required_if' => 'Bitte wählen Sie eine Abteilung.',
        ]);

        if ($validator->fails()) {
            // Wichtig: Redirect zurück mit Fehlern UND Input, damit das Modal offen bleibt
            return back()->withErrors($validator, 'createRole') // Fehler-Bag benennen
                         ->withInput()
                         ->with('open_modal', 'createRoleModal'); // Signal zum Öffnen des Modals
        }

        $roleName = strtolower(trim($request->name));
        $roleType = $request->role_type;
        $departmentId = $request->department_id;
        $role = null;

        try {
            DB::beginTransaction();

            // 1. Immer die Spatie-Rolle erstellen
            $role = Role::create(['name' => $roleName]);

            // 2. Abhängig vom Typ zusätzliche Aktionen
            if ($roleType === 'rank') {
                // Neuen Rang erstellen (Level initial auf 0 setzen, muss sortiert werden)
                Rank::create([
                    'name' => $roleName, 
                    'level' => 0 // Oder Rank::max('level') + 1 / Rank::min('level') - 1
                ]);
                // Hier könnte man updateRankOrder aufrufen oder den User informieren
            } elseif ($roleType === 'department') {
                // Rolle der ausgewählten Abteilung zuweisen
                $department = Department::find($departmentId);
                if ($department) {
                    $department->roles()->attach($role->id);
                } else {
                    // Sollte durch Validation verhindert werden, aber sicher ist sicher
                    throw new \Exception("Ausgewählte Abteilung nicht gefunden."); 
                }
            }
            // Für 'other' ist nichts weiter zu tun

            DB::commit();

            // Logging
            $logDescription = "Neue Rolle '{$role->name}' (Typ: {$roleType}) erstellt.";
            if ($roleType === 'department' && isset($department)) {
                $logDescription .= " Abteilung: {$department->name}.";
            }
            ActivityLog::create([
                'user_id' => Auth::id(), 'log_type' => 'ROLE', 'action' => 'CREATED',
                'target_id' => $role->id, 'description' => $logDescription,
            ]);

            PotentiallyNotifiableActionOccurred::dispatch(
                'Admin\RoleController@store', Auth::user(), $role, Auth::user()
            );

            return redirect()->route('admin.roles.index', ['role' => $role->id])->with('success', 'Rolle erfolgreich erstellt.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Fehler beim Erstellen der Rolle {$roleName}: " . $e->getMessage());
            return back()->with('error', 'Fehler beim Erstellen der Rolle: ' . $e->getMessage())->withInput();
        }
    }/**
     * Aktualisiert Rolle, Berechtigungen UND Typ/Department-Zugehörigkeit.
     * (STARK ANGEPASST)
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
            // Redirect zur Index-Seite mit Fehlern und der ausgewählten Rolle
            return redirect()->route('admin.roles.index', ['role' => $role->id])
                             ->withErrors($validator, 'updateRole') // Fehler-Bag benennen
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
        if ($rankEntry) {
            $oldType = 'rank';
        } elseif ($deptRole = DB::table('department_role')->where('role_id', $role->id)->first()) {
            $oldType = 'department';
            $oldDepartmentId = $deptRole->department_id;
        }

        try {
            DB::beginTransaction();

            // 1. Namen und Berechtigungen aktualisieren
            $role->update(['name' => $newName]);
            $role->syncPermissions($permissionsToSync);

            // 2. Typ-Änderungen verarbeiten
            if ($oldType !== $newType || ($newType === 'department' && $oldDepartmentId != $newDepartmentId)) {
                
                // --- Bereinigung basierend auf altem Typ ---
                if ($oldType === 'rank' && $rankEntry) {
                    $rankEntry->delete(); // Rank-Eintrag löschen
                    // Re-Ordering Logik hier aufrufen, wenn nötig
                } elseif ($oldType === 'department') {
                    // Alte Department-Verknüpfung lösen
                    DB::table('department_role')->where('role_id', $role->id)->delete(); 
                }

                // --- Hinzufügen basierend auf neuem Typ ---
                if ($newType === 'rank') {
                    // Neuen Rank-Eintrag erstellen (Level 0)
                     Rank::firstOrCreate(
                        ['name' => $newName], // Sicherstellen, dass er nicht doppelt erstellt wird
                        ['level' => 0]
                     );
                } elseif ($newType === 'department') {
                    // Neue Department-Verknüpfung erstellen
                    $department = Department::find($newDepartmentId);
                    if ($department) {
                        $department->roles()->attach($role->id);
                    } else {
                         throw new \Exception("Ausgewählte Abteilung nicht gefunden."); 
                    }
                }
            }
            // Wenn Name geändert wurde und es ein Rang war/ist, auch im Rank-Table aktualisieren
            if ($oldName !== $newName && ($oldType === 'rank' || $newType === 'rank')) {
                 Rank::whereRaw('LOWER(name) = ?', [strtolower($oldName)])
                     ->update(['name' => $newName]);
             }


            DB::commit();

            // Logging
            $logDescription = "Rolle '{$oldName}' aktualisiert.";
            // ... (detaillierte Log-Beschreibung für Namen, Perms, Typ-Änderung) ...
             if ($oldName !== $newName) $logDescription .= " Neuer Name: '{$newName}'.";
             if ($oldType !== $newType) $logDescription .= " Typ geändert: {$oldType} -> {$newType}.";
             if ($newType === 'department') $logDescription .= " Abteilung: " . (Department::find($newDepartmentId)->name ?? 'Unbekannt') . ".";
            // ... (Perms hinzufügen/entfernen Logik) ...

            ActivityLog::create([ /* ... Log-Daten ... */ 'description' => $logDescription ]);
            PotentiallyNotifiableActionOccurred::dispatch(/* ... Event Daten ... */);

            return redirect()->route('admin.roles.index', ['role' => $role->id])->with('success', 'Rolle erfolgreich aktualisiert.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Fehler beim Aktualisieren der Rolle {$role->id}: " . $e->getMessage());
            return redirect()->route('admin.roles.index', ['role' => $role->id])->with('error', 'Fehler beim Aktualisieren: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Entfernt die Rolle UND ggf. den zugehörigen Rank-Eintrag.
     * (ANGEPASST)
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
        $deletedRoleData = $role->toArray(); 

        try {
            DB::beginTransaction();

            // Prüfen, ob es ein Rang ist und diesen ggf. löschen
            $rankEntry = Rank::whereRaw('LOWER(name) = ?', [strtolower($roleName)])->first();
            if ($rankEntry) {
                $rankEntry->delete();
                // Hier könnte man updateRankOrder aufrufen oder User informieren
            }
            
            // Zugehörigkeit zu Departments löschen (wird durch Cascade Constraint erledigt, wenn richtig gesetzt, ansonsten manuell):
            // DB::table('department_role')->where('role_id', $roleId)->delete(); 

            // Die Rolle selbst löschen
            $role->delete(); 

            DB::commit();

            ActivityLog::create([ /* ... Log-Daten ... */ 'description' => "Rolle '{$roleName}' gelöscht."]);
            PotentiallyNotifiableActionOccurred::dispatch(/* ... Event Daten ... */);

            return redirect()->route('admin.roles.index')->with('success', "Rolle '{$roleName}' erfolgreich gelöscht.");

        } catch (\Exception $e) {
             DB::rollBack();
             Log::error("Fehler beim Löschen der Rolle {$roleId}: " . $e->getMessage());
             return back()->with('error', 'Fehler beim Löschen der Rolle.');
        }
    }
    
    // ===========================================
    // NEUE DEPARTMENT CRUD METHODEN
    // ===========================================

    /**
     * Speichert eine neue Abteilung.
     */
    public function storeDepartment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'department_name' => 'required|string|max:255|unique:departments,name',
            // Füge hier ggf. Validierung für 'leitung_role_name' und 'min_rank_level_to_assign_leitung' hinzu,
            // falls du diese Felder auch im Modal abfragen willst.
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
                 // Standardwerte oder aus Request holen
                 'leitung_role_name' => $request->leitung_role_name ?? '', 
                 'min_rank_level_to_assign_leitung' => $request->min_rank_level_to_assign_leitung ?? 0, 
            ]);

            ActivityLog::create([
                'user_id' => Auth::id(), 'log_type' => 'DEPARTMENT', 'action' => 'CREATED',
                'target_id' => $department->id, 'description' => "Neue Abteilung '{$department->name}' erstellt.",
            ]);
            // Kein Event hier nötig, oder?

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
         ], [ /* ... Fehlermeldungen ... */ ]);

         // Fehlerbehandlung mit speziellem Fehler-Bag und Modal-Signal
         if ($validator->fails()) {
              return back()->withErrors($validator, 'editDepartment_' . $department->id) // Eindeutiger Bag-Name
                           ->withInput()
                           ->with('open_modal', 'editDepartmentModal_' . $department->id); // Eindeutiges Signal
         }

        try {
            $oldName = $department->name;
            $department->update([
                'name' => $request->edit_department_name,
                'leitung_role_name' => $request->edit_leitung_role_name ?? $department->leitung_role_name,
                'min_rank_level_to_assign_leitung' => $request->edit_min_rank_level_to_assign_leitung ?? $department->min_rank_level_to_assign_leitung,
            ]);

            $logDescription = "Abteilung '{$oldName}' aktualisiert.";
            if ($oldName !== $department->name) $logDescription .= " Neuer Name: '{$department->name}'.";
            // Ggf. Log für andere Felder hinzufügen

            ActivityLog::create([ /* ... Log-Daten ... */ 'description' => $logDescription ]);

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
        // Sicherheitsprüfung: Abteilung löschen, wenn Rollen zugewiesen sind?
        if ($department->roles()->count() > 0) {
             return back()->with('error', 'Abteilung kann nicht gelöscht werden, da ihr noch Rollen zugewiesen sind.');
        }

        try {
            $deptName = $department->name;
            $deptId = $department->id;
            
            // Abteilung löschen (Pivot-Einträge sollten durch Cascade gelöscht werden, wenn in Migration gesetzt)
            $department->delete(); 

            ActivityLog::create([ /* ... Log-Daten ... */ 'description' => "Abteilung '{$deptName}' gelöscht." ]);

            return redirect()->route('admin.roles.index')->with('success', 'Abteilung erfolgreich gelöscht.');

        } catch (\Exception $e) {
             Log::error("Fehler beim Löschen der Abteilung {$department->id}: " . $e->getMessage());
             return back()->with('error', 'Fehler beim Löschen der Abteilung.');
        }
    }
}