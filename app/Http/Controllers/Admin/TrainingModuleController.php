<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainingModule;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TrainingModuleController extends Controller
{
    /**
     * Apply the policy to all resource methods.
     */
    public function __construct()
    {
        // This automatically maps methods like index() to viewAny(), create() to create(), etc.
        $this->authorizeResource(TrainingModule::class, 'module');
    }

    /**
     * Display a listing of the training modules.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $modules = TrainingModule::latest()->paginate(20);
        return view('admin.training_modules.index', compact('modules'));
    }

    /**
     * Show the form for creating a new training module.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin.training_modules.create');
    }

    /**
     * Store a newly created training module in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:training_modules',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
        ]);

        $module = TrainingModule::create($validated);
        
        // Log the activity
        ActivityLog::create([
            'user_id' => Auth::id(),
            'log_type' => 'TRAINING_MODULE',
            'action' => 'CREATED',
            'target_id' => $module->id,
            'description' => "Ausbildungsmodul '{$module->name}' wurde erstellt.",
        ]);

        return redirect()->route('admin.modules.index')->with('success', 'Ausbildungsmodul erfolgreich erstellt.');
    }

    /**
     * Display the specified training module and its assigned users.
     *
     * @param  \App\Models\TrainingModule  $module
     * @return \Illuminate\View\View
     */
    public function show(TrainingModule $module)
    {
        // Eager load the users assigned to this module
        $module->load('users');
        return view('admin.training_modules.show', compact('module'));
    }

    /**
     * Show the form for editing the specified training module.
     *
     * @param  \App\Models\TrainingModule  $module
     * @return \Illuminate\View\View
     */
    public function edit(TrainingModule $module)
    {
        return view('admin.training_modules.edit', compact('module'));
    }

    /**
     * Update the specified training module in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\TrainingModule  $module
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, TrainingModule $module)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:training_modules,name,' . $module->id,
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
        ]);

        $module->update($validated);
        
        // Log the activity
        ActivityLog::create([
            'user_id' => Auth::id(),
            'log_type' => 'TRAINING_MODULE',
            'action' => 'UPDATED',
            'target_id' => $module->id,
            'description' => "Ausbildungsmodul '{$module->name}' wurde aktualisiert.",
        ]);

        return redirect()->route('admin.modules.index')->with('success', 'Ausbildungsmodul erfolgreich aktualisiert.');
    }

    /**
     * Remove the specified training module from storage.
     *
     * @param  \App\Models\TrainingModule  $module
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(TrainingModule $module)
    {
        // Store details for the log before deleting
        $moduleName = $module->name;
        $moduleId = $module->id;

        $module->delete();

        // Log the activity
        ActivityLog::create([
            'user_id' => Auth::id(),
            'log_type' => 'TRAINING_MODULE',
            'action' => 'DELETED',
            'target_id' => $moduleId,
            'description' => "Ausbildungsmodul '{$moduleName}' wurde gelöscht.",
        ]);

        return redirect()->route('admin.modules.index')->with('success', 'Ausbildungsmodul erfolgreich gelöscht.');
    }
}
 