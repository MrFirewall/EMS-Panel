<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog; // NEU
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // NEU
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Permission::class);
        $permissions = Permission::latest()->get();
        return view('admin.permissions.index', compact('permissions'));
    }

    public function create()
    {
        $this->authorize('create', Permission::class);
        return view('admin.permissions.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', Permission::class);

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

        $superAdminRole = Role::findByName('super-admin');
        $directorRole = Role::findByName('ems-director');
        $superAdminRole->givePermissionTo($permission);
        $directorRole->givePermissionTo($permission);

        return redirect()->route('admin.permissions.index')
                         ->with('success', 'Berechtigung erfolgreich erstellt und zugewiesen.');
    }

    public function edit(Permission $permission)
    {
        $this->authorize('update', $permission);
        return view('admin.permissions.edit', compact('permission'));
    }

    public function update(Request $request, Permission $permission)
    {
        $this->authorize('update', $permission);

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
        
        $superAdminRole = Role::findByName('super-admin');
        $directorRole = Role::findByName('ems-director');
        $superAdminRole->givePermissionTo($permission);
        $directorRole->givePermissionTo($permission);

        return redirect()->route('admin.permissions.index')
                         ->with('success', 'Berechtigung erfolgreich aktualisiert.');
    }

    public function destroy(Permission $permission)
    {
        $this->authorize('delete', $permission);
        
        $permissionName = $permission->name; // Namen vor dem Löschen speichern
        $permissionId = $permission->id;
        
        $permission->delete();

        // ActivityLog-Eintrag erstellen
        ActivityLog::create([
            'user_id' => Auth::id(),
            'log_type' => 'PERMISSION',
            'action' => 'DELETED',
            'target_id' => $permissionId,
            'description' => "Berechtigung '{$permissionName}' gelöscht.",
        ]);

        return redirect()->route('admin.permissions.index')
                         ->with('success', 'Berechtigung erfolgreich gelöscht.');
    }
}