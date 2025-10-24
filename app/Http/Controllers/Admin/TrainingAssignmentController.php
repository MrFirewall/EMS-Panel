<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\TrainingModule;
use App\Models\Evaluation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Auth hinzufügen
use App\Models\ActivityLog;          // ActivityLog hinzufügen
use App\Events\PotentiallyNotifiableActionOccurred; // Event hinzufügen
use Illuminate\Support\Facades\Notification; // Notification Fassade (optional, falls direkt benötigt)
use App\Notifications\GeneralNotification; // GeneralNotification Klasse

class TrainingAssignmentController extends Controller
{
    /**
     * Weist einen Benutzer einem Modul zu und markiert den Antrag als erledigt.
     */
    public function assign(User $user, TrainingModule $module, Evaluation $evaluation)
    {
        // Policy-Check, ob der eingeloggte User das darf
        // Annahme: Es gibt eine Policy-Methode 'assignUser' oder ähnlich
        $this->authorize('assignUser', TrainingModule::class); // Passe die Policy-Methode an

        // 1. Benutzer dem Modul zuweisen und Status auf "in Ausbildung" setzen
        // Optional: Füge 'assigned_at' hinzu, falls deine Pivot-Tabelle das unterstützt
        $user->trainingModules()->syncWithoutDetaching([
            $module->id => [
                'status' => 'in_ausbildung',
                'updated_at' => now() // Optional
             ]
        ]);

        // 2. Den ursprünglichen Antrag als "erledigt" markieren
        $evaluation->update(['status' => 'processed']);

        // 3. ActivityLog-Eintrag erstellen
        $adminUser = Auth::user(); // Der Admin, der die Aktion ausführt
        ActivityLog::create([
            'user_id' => $adminUser->id,
            'log_type' => 'TRAINING_ASSIGNMENT',
            'action' => 'ASSIGNED',
            'target_id' => $user->id, // Ziel ist der zugewiesene Benutzer
            'description' => "{$user->name} wurde von {$adminUser->name} dem Modul '{$module->name}' zugewiesen (Antrag #{$evaluation->id}).",
        ]);

        // 4. Benachrichtigung via Event auslösen
        PotentiallyNotifiableActionOccurred::dispatch(
            'TrainingAssignmentController@assign', // Action Name
            $user,              // Der Benutzer, der die Aktion AUSLÖST (im Sinne von betroffen ist)
            $module,            // Das zugehörige Modul
            $adminUser          // Der Admin, der die Aktion DURCHFÜHRT
        );

        return redirect()->back(); // Ohne success-Meldung
    }
}

