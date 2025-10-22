<?php

namespace App\Http\Controllers;

use App\Models\Citizen;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Events\PotentiallyNotifiableActionOccurred; // Event hinzufügen

class CitizenController extends Controller
{
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
            'blood_type' => 'nullable|string|max:10',
            'allergies' => 'nullable|string',
            'preexisting_conditions' => 'nullable|string',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:50',
        ]);

        $citizen = Citizen::create($validated);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'log_type' => 'CITIZEN',
            'action' => 'CREATED',
            'target_id' => $citizen->id,
            'description' => "Patientenakte für '{$citizen->name}' erstellt.",
        ]);

        // --- BENACHRICHTIGUNG VIA EVENT ---
        PotentiallyNotifiableActionOccurred::dispatch(
            'CitizenController@store', // Action Name
            Auth::user(),                  // Der erstellte Bürger (als triggering User/Context)
            $citizen,                  // Das zugehörige Modell
            Auth::user()               // Der Ersteller (Admin/Mitarbeiter)
        );
        // ---------------------------------

        return redirect()->route('citizens.show', $citizen); // Ohne success
    }

    public function show(Citizen $citizen)
    {
        $citizen->load(['reports' => function ($query) {
            $query->latest();
        }, 'prescriptions.user']);

        return view('citizens.show', compact('citizen'));
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
            'blood_type' => 'nullable|string|max:10',
            'allergies' => 'nullable|string',
            'preexisting_conditions' => 'nullable|string',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:50',
        ]);

        // Clone Citizen object BEFORE update to pass old state if needed (optional)
        // $citizenBeforeUpdate = clone $citizen;

        $citizen->update($validated);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'log_type' => 'CITIZEN',
            'action' => 'UPDATED',
            'target_id' => $citizen->id,
            'description' => "Patientenakte für '{$citizen->name}' aktualisiert.",
        ]);

        // --- BENACHRICHTIGUNG VIA EVENT ---
        PotentiallyNotifiableActionOccurred::dispatch(
            'CitizenController@update', // Action Name
            Auth::user(),                  // Der aktualisierte Bürger
            $citizen,                   // Das zugehörige Modell
            Auth::user(),               // Der Bearbeiter
            // ['old_data' => $citizenBeforeUpdate->toArray()] // Optional: Alter Zustand
        );
        // ---------------------------------

        return redirect()->route('citizens.show', $citizen); // Ohne success
    }

    public function destroy(Citizen $citizen)
    {
        $citizenName = $citizen->name;
        $citizenId = $citizen->id;

        // Erstelle ein temporäres Objekt mit den relevanten Daten für das Event
        $deletedData = (object) [
            'id' => $citizenId,
            'name' => $citizenName,
            // Füge hier ggf. weitere Felder hinzu, die der Listener benötigt
        ];

        $citizen->delete();

        ActivityLog::create([
            'user_id' => Auth::id(),
            'log_type' => 'CITIZEN',
            'action' => 'DELETED',
            'target_id' => $citizenId,
            'description' => "Patientenakte für '{$citizenName}' ({$citizenId}) gelöscht.",
        ]);

        // --- BENACHRICHTIGUNG VIA EVENT ---
        PotentiallyNotifiableActionOccurred::dispatch(
            'CitizenController@destroy', // Action Name
            Auth::user(),                       // Kein spezifischer triggering User mehr vorhanden
            $deletedData,               // Temporäres Objekt mit alten Daten
            Auth::user()                // Der Löschende
        );
        // ---------------------------------

        return redirect()->route('citizens.index'); // Ohne success
    }
}
