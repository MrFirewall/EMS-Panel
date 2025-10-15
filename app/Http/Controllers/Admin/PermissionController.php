<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    /**
     * Setzt die Middleware für den Controller.
     * Jede Methode wird durch eine spezifische 'can'-Middleware geschützt.
     */
    public function __construct()
    {
        $this->middleware('can:permissions.view')->only('index');
        $this->middleware('can:permissions.create')->only(['create', 'store']);
        $this->middleware('can:permissions.edit')->only(['edit', 'update']);
        $this->middleware('can:permissions.delete')->only('destroy');
    }
    /**
     * Zeigt eine Liste aller Berechtigungen an.
     */
    public function index()
    {
        $permissions = Permission::latest()->paginate(15);
        return view('admin.permissions.index', compact('permissions'));
    }

    /**
     * Zeigt das Formular zum Erstellen einer neuen Berechtigung an.
     */
    public function create()
    {
        return view('admin.permissions.create');
    }

    /**
     * Speichert eine neue Berechtigung in der Datenbank.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:permissions,name',
            'description' => 'nullable|string|max:255', // NEU
        ]);

        Permission::create($validated); // Funktioniert dank Mass Assignment

        return redirect()->route('admin.permissions.index')
                         ->with('success', 'Berechtigung erfolgreich erstellt.');
    }

    /**
     * Zeigt das Formular zum Bearbeiten einer Berechtigung an.
     */
    public function edit(Permission $permission)
    {
        return view('admin.permissions.edit', compact('permission'));
    }

    /**
     * Aktualisiert eine bestehende Berechtigung.
     */
    public function update(Request $request, Permission $permission)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:permissions,name,' . $permission->id,
            'description' => 'nullable|string|max:255', // NEU
        ]);

        $permission->update($validated);

        return redirect()->route('admin.permissions.index')
                         ->with('success', 'Berechtigung erfolgreich aktualisiert.');
    }

    /**
     * Löscht eine Berechtigung aus der Datenbank.
     */
    public function destroy(Permission $permission)
    {
        $permission->delete();

        return redirect()->route('admin.permissions.index')
                         ->with('success', 'Berechtigung erfolgreich gelöscht.');
    }
}