<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog; // NEU: Für das Logging
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    /**
     * Schützt den Controller mit den entsprechenden Berechtigungen.
     */
    public function __construct()
    {
        $this->middleware('can:roles.view')->only('index');
        $this->middleware('can:roles.create')->only('store');
        $this->middleware('can:roles.edit')->only('update');
        $this->middleware('can:roles.delete')->only('destroy');
    }
    
    /**
     * Zeigt die Rollenliste und die Bearbeitungsansicht für die ausgewählte Rolle an.
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // Alle Rollen laden und die Anzahl der Benutzer mitzählen
        $roles = Role::withCount('users')->get();
        
        // Alle verfügbaren Berechtigungen laden, gruppiert nach dem Modul
        $permissions = Permission::all()->sortBy('name')->groupBy(function ($item) {
            $parts = explode('-', $item->name, 2);
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

        return view('admin.roles.index', compact('roles', 'permissions', 'currentRole', 'currentRolePermissions'));
    }

    /**
     * Speichert eine neu erstellte Rolle im Storage und erstellt einen Log-Eintrag.
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
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

        // LOGGING HINZUFÜGEN
        ActivityLog::create([
            'user_id' => Auth::id(),
            'log_type' => 'ROLE',
            'action' => 'CREATED',
            'target_id' => $role->id,
            'description' => "Neue Rolle '{$role->name}' erstellt.",
        ]);

        return redirect()->route('admin.roles.index', ['role' => $role->id])
                         ->with('success', "Rolle '{$role->name}' erfolgreich erstellt.");
    }

    /**
     * Aktualisiert die Berechtigungen für die angegebene Rolle und erstellt einen Log-Eintrag.
     * @param Request $request
     * @param \Spatie\Permission\Models\Role $role
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Role $role)
    {
        // KORREKTUR: Wir prüfen auf die Standard-Super-Admin Rolle (oder du passt es an deine unantastbare Rolle an)
        if ($role->name === 'ems-director') { 
            return back()->with('error', 'Die Standard Admin Rolle kann nicht geändert werden.');
        }

        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);
        
        $oldPermissions = $role->permissions->pluck('name')->toArray();

        try {
            DB::beginTransaction();

            // 1. Rollenname aktualisieren
            $role->update(['name' => strtolower(trim($validatedData['name']))]);

            // 2. Berechtigungen synchronisieren
            $permissionsToSync = $validatedData['permissions'] ?? [];
            $role->syncPermissions($permissionsToSync);

            DB::commit();
            
            // LOGGING HINZUFÜGEN
            $diff = array_merge(
                array_diff($permissionsToSync, $oldPermissions),
                array_diff($oldPermissions, $permissionsToSync)
            );
            $description = "Rolle '{$role->name}' aktualisiert. Permissions geändert: " . implode(', ', $diff);

            ActivityLog::create([
                'user_id' => Auth::id(),
                'log_type' => 'ROLE',
                'action' => 'UPDATED',
                'target_id' => $role->id,
                'description' => $description,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Fehler beim Aktualisieren der Rolle und Berechtigungen: ' . $e->getMessage());
        }

        return redirect()->route('admin.roles.index', ['role' => $role->id])
                         ->with('success', "Rolle '{$role->name}' und Berechtigungen erfolgreich aktualisiert.");
    }

    /**
     * Entfernt die angegebene Rolle aus dem Storage und erstellt einen Log-Eintrag.
     * @param \Spatie\Permission\Models\Role $role
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Role $role)
    {
        if ($role->name === 'ems-director' || $role->users()->count() > 0) {
            $error = ($role->name === 'ems-director') 
                ? 'Die Standard Admin Rolle kann nicht gelöscht werden.' 
                : 'Rolle kann nicht gelöscht werden, da noch Benutzer zugewiesen sind.';
            return back()->with('error', $error);
        }

        $roleName = $role->name;
        $role->delete();
        
        // LOGGING HINZUFÜGEN
        ActivityLog::create([
            'user_id' => Auth::id(),
            'log_type' => 'ROLE',
            'action' => 'DELETED',
            'target_id' => $role->id,
            'description' => "Rolle '{$roleName}' gelöscht.",
        ]);

        return redirect()->route('admin.roles.index')
                         ->with('success', "Rolle '{$roleName}' wurde erfolgreich gelöscht.");
    }
}