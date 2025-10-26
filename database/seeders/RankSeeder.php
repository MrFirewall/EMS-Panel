<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rank;
// Schema-Import wird hier nicht benötigt

class RankSeeder extends Seeder
{
    public function run()
    {
        // Dein altes Array
        $rankHierarchy = [
            'chief'         => 11,
            'deputy chief'  => 10,
            'doctor'        => 9,
            'captain'       => 8,
            'lieutenant'    => 7,
            'supervisor'    => 6,
            's-emt'         => 5,
            'paramedic'     => 4,
            'a-emt'         => 3,
            'emt'           => 2,
            'trainee'       => 1,
        ];

        // Entferne die Zeile Rank::truncate(); komplett.
        
        // --- ÄNDERUNG: Nutze updateOrCreate statt create ---
        foreach ($rankHierarchy as $name => $level) {
            Rank::updateOrCreate(
                ['name' => $name],    // Suche nach einem Rang mit diesem Namen
                ['level' => $level]  // Erstelle/aktualisiere ihn mit diesem Level
            );
        }
    }
}