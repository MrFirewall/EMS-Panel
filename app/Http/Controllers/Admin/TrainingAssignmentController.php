<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\TrainingModule;
use App\Models\Evaluation;
use Illuminate\Http\Request;

class TrainingAssignmentController extends Controller
{
    /**
     * Weist einen Benutzer einem Modul zu und markiert den Antrag als erledigt.
     */
    public function assign(User $user, TrainingModule $module, Evaluation $evaluation)
    {
        // Policy-Check, ob der eingeloggte User das darf
        $this->authorize('create', TrainingModule::class); // Beispiel-Policy

        // 1. Benutzer dem Modul zuweisen und Status auf "in Ausbildung" setzen
        $user->trainingModules()->syncWithoutDetaching([
            $module->id => ['status' => 'in_ausbildung']
        ]);

        // 2. Den ursprünglichen Antrag als "erledigt" markieren
        $evaluation->update(['status' => 'processed']);

        // Optional: ActivityLog-Eintrag

        return redirect()->back()->with('success', "{$user->name} wurde erfolgreich für das Modul '{$module->name}' zugewiesen. Der Antrag wurde archiviert.");
    }
}
