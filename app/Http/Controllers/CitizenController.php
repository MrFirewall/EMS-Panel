<?php

namespace App\Http\Controllers;

use App\Models\Citizen;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CitizenController extends Controller
{
     /**
     * Verknüpft den Controller mit der CitizenPolicy.
     */
    public function __construct()
    {
        $this->authorizeResource(Citizen::class, 'citizen');
    }
    public function index(Request $request)
    {
        $query = Citizen::query()->latest();

        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('phone_number', 'like', "%{$searchTerm}%")
                  ->orWhere('address', 'like', "%{$searchTerm}%");
        }

        $citizens = $query->paginate(20)->withQueryString();
        return view('citizens.index', compact('citizens'));
    }

    public function create()
    {
        return view('citizens.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:citizens,name',
            'date_of_birth' => 'nullable|date',
            'phone_number' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $citizen = Citizen::create($validated);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'log_type' => 'CITIZEN',
            'action' => 'CREATED',
            'target_id' => $citizen->id,
            'description' => "Bürgerakte für '{$citizen->name}' erstellt.",
        ]);

        return redirect()->route('citizens.index')->with('success', 'Bürgerakte erfolgreich erstellt.');
    }
    
    public function edit(Citizen $citizen)
    {
        return view('citizens.edit', compact('citizen'));
    }

    public function update(Request $request, Citizen $citizen)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:citizens,name,' . $citizen->id,
            'date_of_birth' => 'nullable|date',
            'phone_number' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $citizen->update($validated);
        
        ActivityLog::create([
            'user_id' => Auth::id(),
            'log_type' => 'CITIZEN',
            'action' => 'UPDATED',
            'target_id' => $citizen->id,
            'description' => "Bürgerakte für '{$citizen->name}' aktualisiert.",
        ]);

        return redirect()->route('citizens.index')->with('success', 'Bürgerakte erfolgreich aktualisiert.');
    }

    public function destroy(Citizen $citizen)
    {
        $citizenName = $citizen->name;
        $citizenId = $citizen->id;
        $citizen->delete();

        ActivityLog::create([
            'user_id' => Auth::id(),
            'log_type' => 'CITIZEN',
            'action' => 'DELETED',
            'target_id' => $citizenId,
            'description' => "Bürgerakte für '{$citizenName}' ({$citizenId}) gelöscht.",
        ]);

        return redirect()->route('citizens.index')->with('success', 'Bürgerakte gelöscht.');
    }
}
