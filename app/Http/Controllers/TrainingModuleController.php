<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TrainingModule;
use App\Models\ActivityLog;
use App\Models\User; // Für Benachrichtigungslogik
use App\Notifications\GeneralNotification; // Für Benachrichtigungslogik
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification; // Für Benachrichtigungslogik
use App\Events\PotentiallyNotifiableActionOccurred; // Event hinzufügen

class TrainingModuleController extends Controller
{
    /**
     * Apply the policy to all resource methods.
     */
    public function __construct()
    {
        // This automatically maps methods like index() to viewAny(), create() to create(), etc.
        $this->authorizeResource(TrainingModule::class, 'module');

        // Middleware für die signUp Methode (nur eingeloggte User dürfen)
        $this->middleware('auth')->only('signUp'); // Stellt sicher, dass nur eingeloggte User die Methode aufrufen
    }

    /**
     * Display a listing of the training modules.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $modules = TrainingModule::latest()->paginate(20);
        return view('training_modules.index', compact('modules'));
    }

    /**
     * Show the form for creating a new training module.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('training_modules.create');
    }

    /**
     * Store a newly created training module in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:training_modules',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
        ]);

        /** @var User $creator */
        $creator = Auth::user();
        $module = TrainingModule::create($validated);

        // Log the activity
        ActivityLog::create([
            'user_id' => $creator->id,
            'log_type' => 'TRAINING_MODULE',
            'action' => 'CREATED',
            'target_id' => $module->id,
            'description' => "Ausbildungsmodul '{$module->name}' wurde erstellt.",
        ]);

        // --- BENACHRICHTIGUNG VIA EVENT ---
        // KORREKTUR: $creator (der handelnde Admin) als triggeringUser verwenden, da null nicht erlaubt ist.
        PotentiallyNotifiableActionOccurred::dispatch(
            'TrainingModuleController@store', // Action
            $creator, // triggeringUser
            $module, // relatedModel
            $creator // actorUser
        );
        // ---------------------------------

        // Erfolgsmeldung entfernt
        return redirect()->route('modules.index');
    }

    /**
     * Display the specified training module and its assigned users.
     *
     * @param  \App\Models\TrainingModule  $module
     * @return \Illuminate\View\View
     */
    public function show(TrainingModule $module)
    {
        // Eager load the users assigned to this module
        $module->load('users');
        return view('training_modules.show', compact('module'));
    }

    /**
     * Show the form for editing the specified training module.
     *
     * @param  \App\Models\TrainingModule  $module
     * @return \Illuminate\View\View
     */
    public function edit(TrainingModule $module)
    {
        return view('training_modules.edit', compact('module'));
    }

    /**
     * Update the specified training module in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\TrainingModule  $module
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, TrainingModule $module)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:training_modules,name,' . $module->id,
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
        ]);

        /** @var User $editor */
        $editor = Auth::user();
        $module->update($validated);

        // Log the activity
        ActivityLog::create([
            'user_id' => $editor->id,
            'log_type' => 'TRAINING_MODULE',
            'action' => 'UPDATED',
            'target_id' => $module->id,
            'description' => "Ausbildungsmodul '{$module->name}' wurde aktualisiert.",
        ]);

        // --- BENACHRICHTIGUNG VIA EVENT ---
        // KORREKTUR: $editor (der handelnde Admin) als triggeringUser verwenden, da null nicht erlaubt ist.
        PotentiallyNotifiableActionOccurred::dispatch(
            'TrainingModuleController@update', // 1. Action Name
            $editor, // 2. triggeringUser
            $module, // 3. relatedModel
            $editor // 4. actorUser
        );
        // ---------------------------------

        // Erfolgsmeldung entfernt
        return redirect()->route('modules.index');
    }

    /**
     * Remove the specified training module from storage.
     *
     * @param  \App\Models\TrainingModule  $module
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(TrainingModule $module)
    {
        /** @var User $deleter */
        $deleter = Auth::user();
        // Store details for the log and event before deleting
        $moduleName = $module->name;
        $moduleId = $module->id;

        $module->delete();

        // Log the activity
        ActivityLog::create([
            'user_id' => $deleter->id,
            'log_type' => 'TRAINING_MODULE',
            'action' => 'DELETED',
            'target_id' => $moduleId, // Use stored ID
            'description' => "Ausbildungsmodul '{$moduleName}' wurde gelöscht.",
        ]);

        // --- BENACHRICHTIGUNG VIA EVENT ---
        // KORREKTUR: $deleter (der handelnde Admin) als triggeringUser verwenden, da null nicht erlaubt ist.
        PotentiallyNotifiableActionOccurred::dispatch(
            'TrainingModuleController@destroy', // Action Name
            $deleter, // triggeringUser
            null, // relatedModel
            $deleter, // actorUser
            ['name' => $moduleName] // additionalData
        );
        // ---------------------------------

        // Erfolgsmeldung entfernt
        return redirect()->route('modules.index');
    }

    /**
     * Meldet den aktuell eingeloggten Benutzer für das angegebene Modul an.
     * (Verschoben aus TrainingAssignmentController, da dies die User-Aktion ist)
     *
     * @param TrainingModule $module
     * @return \Illuminate\Http\RedirectResponse
     */
    public function signUp(TrainingModule $module)
    {
        /** @var User $user */
        $user = Auth::user();

        // Policy-Prüfung (Beispiel: Kann der User sich anmelden?)
        $this->authorize('signUp', $module); // Eigene Policy-Methode erstellen

        // Verhindere doppelte Anmeldung
        if ($module->users()->where('user_id', $user->id)->exists()) {
             // Keine Erfolgs-/Fehlermeldung nötig, einfach zurückleiten
             return redirect()->back();
        }

        // Benutzer anmelden (Status 'angemeldet')
        // HINWEIS: 'status' wurde in der DB entfernt, daher hier NICHT mehr verwenden.
        // Die Zuweisungs-ID muss NULL sein (Selbstanmeldung).
        $module->users()->attach($user->id, ['assigned_by_user_id' => null]);

        // Optional: Logeintrag
        ActivityLog::create([
            'user_id' => $user->id,
            'log_type' => 'TRAINING_SIGNUP',
            'action' => 'SIGNED_UP',
            'target_id' => $module->id,
            'description' => "{$user->name} hat sich für das Modul '{$module->name}' angemeldet (Antrag).",
        ]);

        // --- BENACHRICHTIGUNG VIA EVENT ---
        // KORREKTUR: Named Arguments entfernt.
        PotentiallyNotifiableActionOccurred::dispatch(
            'TrainingModuleController@signUp', // Action
            $user, // triggeringUser
            $module, // relatedModel
            $user // actorUser
        );
        // ------------------------------------

        // Keine Erfolgsmeldung nötig
        return redirect()->back();
    }
}
