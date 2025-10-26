<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Events\PotentiallyNotifiableActionOccurred; // Event hinzufügen

class PermissionController extends Controller
{
    public function __construct()
    {
        // Annahme: Policies sind korrekt für Permission-Model eingerichtet
        $this->authorizeResource(Permission::class, 'permission');
    }

    public function index()
    {
        $permissions = Permission::latest()->get();
        return view('admin.permissions.index', compact('permissions'));
    }

    public function create()
    {
        return view('admin.permissions.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:permissions,name',
            'description' => 'nullable|string|max:255',
        ]);

        $permission = Permission::create($validated);

        // ActivityLog-Eintrag erstellen
        ActivityLog::create([
            'user_id' => Auth::id(),
            'log_type' => 'PERMISSION',
            'action' => 'CREATED',
            'target_id' => $permission->id,
            'description' => "Berechtigung '{$permission->name}' erstellt.",
        ]);

        // Automatisches Zuweisen an Rollen (Beispiel)
        try {
            $superAdminRole = Role::findByName('super-admin', 'web'); // Guard 'web' angeben
            $directorRole = Role::findByName('chief', 'web');   // Guard 'web' angeben
            $superAdminRole->givePermissionTo($permission);
            $directorRole->givePermissionTo($permission);
        } catch (\Spatie\Permission\Exceptions\RoleDoesNotExist $e) {
            // Optional: Fehler loggen, wenn Rolle nicht existiert
            \Illuminate\Support\Facades\Log::warning("Rolle für automatische Berechtigungszuweisung nicht gefunden: " . $e->getMessage());
        }

        // --- BENACHRICHTIGUNG VIA EVENT ---
        PotentiallyNotifiableActionOccurred::dispatch(
            'Admin\PermissionController@store',
            Auth::user(),   // Der Ersteller
            $permission,    // Die neue Berechtigung
            Auth::user()    // Der Akteur
        );
        // ---------------------------------

        return redirect()->route('admin.permissions.index'); // Ohne success
    }

    public function edit(Permission $permission)
    {
        return view('admin.permissions.edit', compact('permission'));
    }

    public function update(Request $request, Permission $permission)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:permissions,name,' . $permission->id,
            'description' => 'nullable|string|max:255',
        ]);

        $permission->update($validated);

        // ActivityLog-Eintrag erstellen
        ActivityLog::create([
            'user_id' => Auth::id(),
            'log_type' => 'PERMISSION',
            'action' => 'UPDATED',
            'target_id' => $permission->id,
            'description' => "Berechtigung '{$permission->name}' aktualisiert.",
        ]);

        // Sicherstellen, dass die Rollen die (ggf. umbenannte) Berechtigung haben
        try {
            $superAdminRole = Role::findByName('super-admin', 'web');
            $directorRole = Role::findByName('chief', 'web');
            // syncPermissions stellt sicher, dass nur die aktuellen Berechtigungen zugewiesen sind
            // Alternative: givePermissionTo, wenn nur hinzugefügt werden soll
             $superAdminRole->syncPermissions(Permission::whereIn('name', $superAdminRole->getPermissionNames())->orWhere('id', $permission->id)->get());
             $directorRole->syncPermissions(Permission::whereIn('name', $directorRole->getPermissionNames())->orWhere('id', $permission->id)->get());
             // oder einfacher, wenn nur hinzugefügt werden soll:
             // $superAdminRole->givePermissionTo($permission);
             // $directorRole->givePermissionTo($permission);
        } catch (\Spatie\Permission\Exceptions\RoleDoesNotExist $e) {
             \Illuminate\Support\Facades\Log::warning("Rolle für automatische Berechtigungszuweisung nicht gefunden: " . $e->getMessage());
        }


        // --- BENACHRICHTIGUNG VIA EVENT ---
        PotentiallyNotifiableActionOccurred::dispatch(
            'Admin\PermissionController@update',
            Auth::user(),   // Der Bearbeiter
            $permission,    // Die aktualisierte Berechtigung
            Auth::user()    // Der Akteur
        );
        // ---------------------------------

        return redirect()->route('admin.permissions.index'); // Ohne success
    }

    public function destroy(Permission $permission)
    {
        $permissionName = $permission->name; // Namen vor dem Löschen speichern
        $permissionId = $permission->id;
        $deletedPermissionData = $permission->toArray(); // Kopie für Event

        $permission->delete();

        // ActivityLog-Eintrag erstellen
        ActivityLog::create([
            'user_id' => Auth::id(),
            'log_type' => 'PERMISSION',
            'action' => 'DELETED',
            'target_id' => $permissionId,
            'description' => "Berechtigung '{$permissionName}' gelöscht.",
        ]);

        // --- BENACHRICHTIGUNG VIA EVENT ---
        PotentiallyNotifiableActionOccurred::dispatch(
            'Admin\PermissionController@destroy',
            Auth::user(),                   // Der Löschende
            (object) $deletedPermissionData,// Übergabe als Objekt
            Auth::user()                    // Der Akteur
        );
        // ---------------------------------

        return redirect()->route('admin.permissions.index'); // Ohne success
    }
}
