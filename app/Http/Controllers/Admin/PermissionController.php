<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController extends Controller
{
    /**
     * Zeigt eine Liste aller Berechtigungen an.
     */
    public function index()
    {
        // Prüft die 'viewAny'-Methode in der PermissionPolicy
        $this->authorize('viewAny', Permission::class);

        $permissions = Permission::latest()->get();
        return view('admin.permissions.index', compact('permissions'));
    }

    /**
     * Zeigt das Formular zum Erstellen einer neuen Berechtigung an.
     */
    public function create()
    {
        // Prüft die 'create'-Methode in der PermissionPolicy
        $this->authorize('create', Permission::class);

        return view('admin.permissions.create');
    }

    /**
     * Speichert eine neue Berechtigung in der Datenbank.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Permission::class);

        $validated = $request->validate([
            'name' => 'required|string|unique:permissions,name',
            'description' => 'nullable|string|max:255',
        ]);

        $permission = Permission::create($validated);

        $superAdminRole = Role::findByName('super-admin');
        $directorRole = Role::findByName('ems-director');
        $superAdminRole->givePermissionTo($permission);
        $directorRole->givePermissionTo($permission);

        return redirect()->route('admin.permissions.index')
                         ->with('success', 'Berechtigung erfolgreich erstellt und zugewiesen.');
    }

    /**
     * Zeigt das Formular zum Bearbeiten einer Berechtigung an.
     */
    public function edit(Permission $permission)
    {
        // Prüft die 'update'-Methode und übergibt die konkrete Berechtigung
        $this->authorize('update', $permission);

        return view('admin.permissions.edit', compact('permission'));
    }

    /**
     * Aktualisiert eine bestehende Berechtigung.
     */
    public function update(Request $request, Permission $permission)
    {
        $this->authorize('update', $permission);

        $validated = $request->validate([
            'name' => 'required|string|unique:permissions,name,' . $permission->id,
            'description' => 'nullable|string|max:255',
        ]);

        $permission->update($validated);
        
        $superAdminRole = Role::findByName('Super-Admin');
        $directorRole = Role::findByName('ems-director');
        $superAdminRole->givePermissionTo($permission);
        $directorRole->givePermissionTo($permission);

        return redirect()->route('admin.permissions.index')
                         ->with('success', 'Berechtigung erfolgreich aktualisiert.');
    }

    /**
     * Löscht eine Berechtigung aus der Datenbank.
     */
    public function destroy(Permission $permission)
    {
        // Prüft die 'delete'-Methode
        $this->authorize('delete', $permission);
        
        $permission->delete();

        return redirect()->route('admin.permissions.index')
                         ->with('success', 'Berechtigung erfolgreich gelöscht.');
    }
}