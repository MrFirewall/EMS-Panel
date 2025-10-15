<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Models\User;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // --- BERECHTIGUNGEN ---
        // 'firstOrCreate' stellt sicher, dass keine Duplikate erstellt werden
        
        // Admin Bereich & Dashboard
        Permission::firstOrCreate(['name' => 'admin.access', 'description' => 'Zugriff auf den Admin-Bereich']);
        
        // Berechtigungen verwalten (NEU & granular)
        Permission::firstOrCreate(['name' => 'permissions.view', 'description' => 'Berechtigungen einsehen']);
        Permission::firstOrCreate(['name' => 'permissions.create', 'description' => 'Berechtigungen erstellen']);
        Permission::firstOrCreate(['name' => 'permissions.edit', 'description' => 'Berechtigungen bearbeiten']);
        Permission::firstOrCreate(['name' => 'permissions.delete', 'description' => 'Berechtigungen löschen']);

        // Rollen verwalten (NEU & granular, ersetzt role.manage & role.create)
        Permission::firstOrCreate(['name' => 'roles.view', 'description' => 'Rollen einsehen']);
        Permission::firstOrCreate(['name' => 'roles.create', 'description' => 'Rollen erstellen']);
        Permission::firstOrCreate(['name' => 'roles.edit', 'description' => 'Rollen bearbeiten (inkl. Rechte zuweisen)']);
        Permission::firstOrCreate(['name' => 'roles.delete', 'description' => 'Rollen löschen']);

        // Ankündigungen
        Permission::firstOrCreate(['name' => 'announcements.view', 'description' => 'Ankündigungen einsehen']);
        Permission::firstOrCreate(['name' => 'announcements.create', 'description' => 'Ankündigungen erstellen']);
        Permission::firstOrCreate(['name' => 'announcements.edit', 'description' => 'Ankündigungen bearbeiten']);
        Permission::firstOrCreate(['name' => 'announcements.delete', 'description' => 'Ankündigungen löschen']);

        // Benutzer
        Permission::firstOrCreate(['name' => 'users.view', 'description' => 'Benutzerliste einsehen']);
        Permission::firstOrCreate(['name' => 'users.create', 'description' => 'Benutzer erstellen']);
        Permission::firstOrCreate(['name' => 'users.edit', 'description' => 'Benutzer bearbeiten']);
        Permission::firstOrCreate(['name' => 'users.delete', 'description' => 'Benutzer löschen']);
        Permission::firstOrCreate(['name' => 'users.manage.record', 'description' => 'Akteneinträge für Benutzer verwalten']);

        // Urlaub (NEU & granular)
        Permission::firstOrCreate(['name' => 'vacations.create', 'description' => 'Eigene Urlaubsanträge erstellen']);
        Permission::firstOrCreate(['name' => 'vacations.manage', 'description' => 'Alle Urlaubsanträge verwalten']);


        // Logs
        Permission::firstOrCreate(['name' => 'logs.view', 'description' => 'System-Logs einsehen']);
        
        // Profil
        Permission::firstOrCreate(['name' => 'profile.view', 'description' => 'Eigenes Profil ansehen']);

        // Einsatzberichte
        Permission::firstOrCreate(['name' => 'reports.view', 'description' => 'Einsatzberichte einsehen']);
        Permission::firstOrCreate(['name' => 'reports.create', 'description' => 'Einsatzberichte erstellen']);
        Permission::firstOrCreate(['name' => 'reports.edit', 'description' => 'Eigene Einsatzberichte bearbeiten']);
        Permission::firstOrCreate(['name' => 'reports.delete', 'description' => 'Eigene Einsatzberichte löschen']);
        Permission::firstOrCreate(['name' => 'reports.manage.all', 'description' => 'Alle Einsatzberichte verwalten (bearbeiten/löschen)']);


        // Bewertungen (NEU & granular)
        // Jeder darf standardmäßig seine eigenen Bewertungen sehen und neue erstellen.
        Permission::firstOrCreate(['name' => 'evaluations.view.own', 'description' => 'Eigene Bewertungen einsehen']);
        Permission::firstOrCreate(['name' => 'evaluations.create', 'description' => 'Bewertungen erstellen']);

        // Nur Admins/Manager dürfen alle Bewertungen sehen.
        Permission::firstOrCreate(['name' => 'evaluations.view.all', 'description' => 'Alle Bewertungen einsehen']);

        

        // --- ROLLEN / GRUPPEN ---
        $role_super_admin = Role::firstOrCreate(['name' => 'Super-Admin', 'guard_name' => 'web']);
        $role_ems_director = Role::firstOrCreate(['name' => 'ems-director', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'assistant-ems-director', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'instructor', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'emergency-doctor', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'paramedic', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'emt', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'emt-trainee', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'praktikant', 'guard_name' => 'web']);
        
        // Rechte zuweisen (givePermissionTo ist intelligent und fügt nur hinzu, was fehlt)
        $all_permissions = Permission::all();
        $role_ems_director->givePermissionTo($all_permissions);
        $role_super_admin->givePermissionTo($all_permissions);

        // Weise JEDER Rolle die Basisfunktionen zu
        $roles = Role::where('name', '!=', 'Super-Admin')->get(); // Super-Admin von Basisrechten ausschließen
        foreach ($roles as $role) {
            $role->givePermissionTo(['evaluations.view.own', 'evaluations.create','profile.view','reports.view', 'reports.create', 'reports.edit', 'reports.delete','vacations.create']);
        }

        //Optional: Weise die Super-Admin Rolle einem bestimmten User zu (z.B. User mit ID 1)
        $user = User::find(1);
        if ($user) {
            $user->assignRole('Super-Admin');
        }
    }
}
