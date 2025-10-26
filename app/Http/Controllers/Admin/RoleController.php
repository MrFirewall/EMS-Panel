<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
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
        $this->middleware('can:roles.view')->only('index');
        $this->middleware('can:roles.create')->only('store');
        // 'updateRankOrder' wird durch die Route (siehe Schritt 1) geschützt
        $this->middleware('can:roles.edit')->only('update'); 
        $this->middleware('can:roles.delete')->only('destroy');
    }

    /**
     * Zeigt die Rollenliste (kategorisiert) und die Bearbeitungsansicht an.
     * (STARK ANGEPASST)
     */
    public function index(Request $request)
    {
        // 1. Alle Daten für die Kategorisierung laden
        
        // Alle Spatie-Rollen mit der Anzahl der Benutzer laden
        $allRoles = Role::withCount('users')->get()->keyBy('name');
        
        // Alle Ränge (aus der 'ranks' Tabelle), sortiert nach dem höchsten Level
        $ranks = Rank::orderBy('level', 'desc')->get();
        
        // Alle Abteilungen mit ihren zugehörigen Spatie-Rollen
        $departments = Department::with('roles')->get();
        
        // 2. Kategorien initialisieren
        $categorizedRoles = [
            'Ranks' => [],
            'Departments' => [],
            'Other' => []
        ];
        
        // Sammlung, um bereits zugeordnete Rollen zu verfolgen
        $processedRoleNames = collect();

        // 3. Ränge zuordnen
        foreach ($ranks as $rank) {
            if ($allRoles->has($rank->name)) {
                $roleModel = $allRoles->get($rank->name);
                // Wir fügen die ID aus der 'ranks'-Tabelle hinzu, 
                // damit wir sie per Drag&Drop sortieren können.
                $roleModel->rank_id = $rank->id; 
                $categorizedRoles['Ranks'][] = $roleModel;
                $processedRoleNames->push($rank->name);
            }
        }

        // 4. Abteilungen zuordnen
        foreach ($departments as $dept) {
            $categorizedRoles['Departments'][$dept->name] = [];
            foreach ($dept->roles as $role) {
                // Nur hinzufügen, wenn die Rolle existiert UND nicht schon als Rang verarbeitet wurde
                if ($allRoles->has($role->name) && !$processedRoleNames->contains($role->name)) {
                    $categorizedRoles['Departments'][$dept->name][] = $allRoles->get($role->name);
                    $processedRoleNames->push($role->name);
                }
            }
        }
        
        // 5. Alle übrigen Rollen zu 'Other' hinzufügen
        $categorizedRoles['Other'] = $allRoles->except($processedRoleNames)->values();

        // 6. Logik für die rechte Spalte (Berechtigungen) - bleibt gleich
        $permissions = Permission::all()->sortBy('name')->groupBy(function ($item) {
            $parts = explode('.', $item->name, 2);
            return $parts[0];
        });

        $currentRole = null;
        $currentRolePermissions = [];

        if ($request->has('role')) {
            $currentRole = Role::findById($request->query('role'));
            if ($currentRole) {
                $currentRolePermissions = $currentRole->permissions->pluck('name')->toArray();
            }
        }

        // 7. Kategorisierte Rollen an die View übergeben
        return view('admin.roles.index', compact(
            'categorizedRoles', // Ersetzt die alte $roles Variable
            'permissions', 
            'currentRole', 
            'currentRolePermissions'
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
            app()->forget(config('permission.cache.key'));

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
     * Speichert eine neu erstellte Rolle.
     * (Keine Änderungen nötig. Neue Rollen landen automatisch in "Andere")
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles,name',
        ], [
            'name.unique' => 'Dieser Rollenname ist bereits vergeben.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $role = Role::create(['name' => strtolower(trim($request->name))]);

        ActivityLog::create([
            'user_id' => Auth::id(), 'log_type' => 'ROLE', 'action' => 'CREATED',
            'target_id' => $role->id, 'description' => "Neue Rolle '{$role->name}' erstellt.",
        ]);

        PotentiallyNotifiableActionOccurred::dispatch(
            'Admin\RoleController@store', Auth::user(), $role, Auth::user()
        );

        return redirect()->route('admin.roles.index', ['role' => $role->id]);
    }

    /**
     * Aktualisiert die Berechtigungen für die angegebene Rolle.
     * (Keine Änderungen nötig)
     */
    public function update(Request $request, Role $role)
    {
        if ($role->name === 'super-admin' || $role->name === 'chief') {
            return back()->with('error', 'Diese Standardrolle kann nicht geändert werden.');
        }

        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $oldName = $role->name;
        $oldPermissions = $role->permissions->pluck('name')->toArray();

        try {
            DB::beginTransaction();
            $role->update(['name' => strtolower(trim($validatedData['name']))]);
            $permissionsToSync = $validatedData['permissions'] ?? [];
            $role->syncPermissions($permissionsToSync);
            DB::commit();

            // ... (Logging-Logik bleibt gleich) ...
            $nameChanged = $oldName !== $role->name;
            $newPermissions = $role->permissions->pluck('name')->toArray();
            $addedPermissions = array_diff($newPermissions, $oldPermissions);
            $removedPermissions = array_diff($oldPermissions, $newPermissions);
            $description = "Rolle '{$oldName}' aktualisiert.";
            // ... (Rest der Log-Beschreibung) ...
            
            ActivityLog::create([
                'user_id' => Auth::id(), 'log_type' => 'ROLE', 'action' => 'UPDATED',
                'target_id' => $role->id, 'description' => $description,
            ]);

            PotentiallyNotifiableActionOccurred::dispatch(
                'Admin\RoleController@update', Auth::user(), $role, Auth::user()
            );

        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error("Fehler beim Aktualisieren der Rolle {$role->id}: " . $e->getMessage());
            return back()->with('error', 'Fehler beim Aktualisieren der Rolle und Berechtigungen.');
        }

        return redirect()->route('admin.roles.index', ['role' => $role->id]);
    }

    /**
     * Entfernt die angegebene Rolle.
     * (Keine Änderungen nötig)
     */
    public function destroy(Role $role)
    {
        if ($role->name === 'super-admin' || $role->name === 'chief' || $role->users()->count() > 0) {
            $error = 'Diese Rolle kann nicht gelöscht werden...';
            return back()->with('error', $error);
        }

        $roleName = $role->name;
        $roleId = $role->id;
        $deletedRoleData = $role->toArray(); 

        $role->delete();

        ActivityLog::create([
            'user_id' => Auth::id(), 'log_type' => 'ROLE', 'action' => 'DELETED',
            'target_id' => $roleId, 'description' => "Rolle '{$roleName}' gelöscht.",
        ]);

        PotentiallyNotifiableActionOccurred::dispatch(
            'Admin\RoleController@destroy', Auth::user(), (object) $deletedRoleData, Auth::user()
        );

        return redirect()->route('admin.roles.index');
    }
}