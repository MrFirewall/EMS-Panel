<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\TrainingModule;
use Illuminate\Http\Request;

class TrainingAssignmentController extends Controller
{
    public function assign(User $user, TrainingModule $module)
    {
        // Optional: Policy-Check, ob der eingeloggte User das darf
        // $this->authorize('assign', TrainingModule::class);

        $user->trainingModules()->syncWithoutDetaching([
            $module->id => ['status' => 'in_ausbildung']
        ]);

        // Optional: Den Antrag (Evaluation) als "bearbeitet" markieren

        return redirect()->back()->with('success', "{$user->name} wurde erfolgreich für das Modul '{$module->name}' zugewiesen.");
    }
}
