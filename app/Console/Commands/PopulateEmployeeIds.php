<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class PopulateEmployeeIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:populate-employee-ids';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fills the employee_id for existing users where it is null or empty.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Suche nach Benutzern mit fehlender Mitarbeiter-ID...');

        // Finde alle Benutzer, bei denen die employee_id leer ist
        $usersToUpdate = User::whereNull('employee_id')->orWhere('employee_id', '')->get();

        if ($usersToUpdate->isEmpty()) {
            $this->info('Alle Benutzer haben bereits eine Mitarbeiter-ID. Nichts zu tun.');
            return 0;
        }

        $this->info($usersToUpdate->count() . ' Benutzer gefunden. Generiere neue IDs...');

        foreach ($usersToUpdate as $user) {
            // Generiere eine einzigartige, 5-stellige ID
            do {
                $newEmployeeId = rand(10000, 99999);
            } while (User::where('employee_id', $newEmployeeId)->exists());

            $user->employee_id = $newEmployeeId;
            $user->save();

            $this->line('-> Benutzer "' . $user->name . '" hat die ID ' . $newEmployeeId . ' erhalten.');
        }

        $this->info('Alle Benutzer wurden erfolgreich aktualisiert!');
        return 0;
    }
}