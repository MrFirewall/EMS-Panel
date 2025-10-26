<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use Spatie\Permission\Models\Role; // Spatie Role
use Illuminate\Support\Facades\DB;

class DepartmentSeeder extends Seeder
{
    public function run()
    {
        // Dein altes Array
        $departmentalRoles = [
            'Rechtsabteilung' => [
                'leitung_role' => 'rechtsabteilung - leitung',
                'min_rank_to_assign_leitung' => 10, // Assistant EMS Director
                'roles' => [
                    'Rechtsabteilung - leitung',
                    'Rechtsabteilung - mitglied',
                ],
            ],
            'Ausbildungsabteilung' => [
                'leitung_role' => 'ausbildungsabteilung - leitung',
                'min_rank_to_assign_leitung' => 10,
                'roles' => [
                    'Ausbildungsabteilung - leitung',
                    'Ausbildungsabteilung - ausbilder',
                    'Ausbildungsabteilung - ausbilder auf probe',
                ],
            ],
            'Personalabteilung' => [
                'leitung_role' => 'personalabteilung - leitung',
                'min_rank_to_assign_leitung' => 10,
                'roles' => [
                    'Personalabteilung - leitung',
                    'Personalabteilung - mitglied',
                ],
            ],
        ];

        // Tabellen leeren
        DB::table('department_role')->truncate();
        Department::truncate();

        foreach ($departmentalRoles as $deptName => $config) {
            
            // 1. Department erstellen
            $department = Department::create([
                'name' => $deptName,
                'leitung_role_name' => $config['leitung_role'],
                'min_rank_level_to_assign_leitung' => $config['min_rank_to_assign_leitung'],
            ]);

            // 2. Rollen finden und verknüpfen
            // WICHTIG: Diese Rollen müssen in der 'roles'-Tabelle existieren!
            // Du brauchst ggf. einen RoleSeeder, der VOR diesem Seeder läuft.
            $roles = Role::whereIn('name', $config['roles'])->get();
            
            if ($roles->count() != count($config['roles'])) {
                 // Hilfreiche Warnung, wenn eine Rolle nicht gefunden wurde
                 $this->command->warn("Warnung: Nicht alle Rollen für Abteilung '$deptName' gefunden.");
            }

            // Rollen an die Pivot-Tabelle anhängen
            $department->roles()->sync($roles);
        }
    }
}