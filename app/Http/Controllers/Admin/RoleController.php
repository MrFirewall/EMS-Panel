<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Events\PotentiallyNotifiableActionOccurred; // Event hinzufügen

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
            // Updated grouping logic to handle permissions without '-'
            $parts = explode('.', $item->name, 2); // Split by '.'
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

        // --- BENACHRICHTIGUNG VIA EVENT ---
        PotentiallyNotifiableActionOccurred::dispatch(
            'Admin\RoleController@store',
            Auth::user(),   // Der Ersteller
            $role,          // Die neue Rolle
            Auth::user()    // Der Akteur
        );
        // ---------------------------------

        return redirect()->route('admin.roles.index', ['role' => $role->id]); // Ohne success
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
        if ($role->name === 'super-admin' || $role->name === 'chief') { // Füge hier alle "geschützten" Rollen hinzu
            return back()->with('error', 'Diese Standardrolle kann nicht geändert werden.');
        }


        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $oldName = $role->name; // Alten Namen speichern für Log
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
            $nameChanged = $oldName !== $role->name;
            $newPermissions = $role->permissions->pluck('name')->toArray(); // Aktuelle holen
            $addedPermissions = array_diff($newPermissions, $oldPermissions);
            $removedPermissions = array_diff($oldPermissions, $newPermissions);

            $description = "Rolle '{$oldName}' aktualisiert.";
            if ($nameChanged) {
                 $description .= " Neuer Name: '{$role->name}'.";
            }
             if (!empty($addedPermissions)) {
                 $description .= " Berechtigungen hinzugefügt: " . implode(', ', $addedPermissions) . ".";
             }
             if (!empty($removedPermissions)) {
                 $description .= " Berechtigungen entfernt: " . implode(', ', $removedPermissions) . ".";
             }


            ActivityLog::create([
                'user_id' => Auth::id(),
                'log_type' => 'ROLE',
                'action' => 'UPDATED',
                'target_id' => $role->id,
                'description' => $description,
            ]);

            // --- BENACHRICHTIGUNG VIA EVENT ---
            PotentiallyNotifiableActionOccurred::dispatch(
                'Admin\RoleController@update',
                Auth::user(),   // Der Bearbeiter
                $role,          // Die aktualisierte Rolle
                Auth::user()    // Der Akteur
            );
            // ---------------------------------

        } catch (\Exception $e) {
            DB::rollBack();
            // Optional: Fehler loggen
            \Illuminate\Support\Facades\Log::error("Fehler beim Aktualisieren der Rolle {$role->id}: " . $e->getMessage());
            return back()->with('error', 'Fehler beim Aktualisieren der Rolle und Berechtigungen.'); // Generische Fehlermeldung
        }


        return redirect()->route('admin.roles.index', ['role' => $role->id]); // Ohne success
    }

    /**
     * Entfernt die angegebene Rolle aus dem Storage und erstellt einen Log-Eintrag.
     * @param \Spatie\Permission\Models\Role $role
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Role $role)
    {
        if ($role->name === 'super-admin' || $role->name === 'chief' || $role->users()->count() > 0) {
            $error = 'Diese Rolle kann nicht gelöscht werden (Standardrolle oder Benutzer zugewiesen).';
            if ($role->name === 'super-admin' || $role->name === 'chief') {
                $error = 'Standardrollen können nicht gelöscht werden.';
            } elseif ($role->users()->count() > 0) {
                $error = 'Rolle kann nicht gelöscht werden, da noch Benutzer zugewiesen sind.';
            }
            return back()->with('error', $error);
        }

        $roleName = $role->name;
        $roleId = $role->id; // ID speichern, da Objekt nach delete() evtl. nicht mehr verfügbar
        $deletedRoleData = $role->toArray(); // Kopie für Event

        $role->delete();

        // LOGGING HINZUFÜGEN
        ActivityLog::create([
            'user_id' => Auth::id(),
            'log_type' => 'ROLE',
            'action' => 'DELETED',
            'target_id' => $roleId, // Gespeicherte ID verwenden
            'description' => "Rolle '{$roleName}' gelöscht.",
        ]);

        // --- BENACHRICHTIGUNG VIA EVENT ---
        PotentiallyNotifiableActionOccurred::dispatch(
            'Admin\RoleController@destroy',
            Auth::user(),               // Der Löschende
            (object) $deletedRoleData,  // Übergabe als Objekt
            Auth::user()                // Der Akteur
        );
        // ---------------------------------

        return redirect()->route('admin.roles.index'); // Ohne success
    }
}
