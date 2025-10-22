<?php

namespace App\Http\Controllers;

use App\Events\PotentiallyNotifiableActionOccurred; // Event hinzufügen
use App\Models\ActivityLog;
use App\Models\Citizen;
use App\Models\Prescription;
use App\Models\User; // Für Typ-Hinting
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PrescriptionController extends Controller
{
    /**
     * Shows the form for creating a new prescription for a citizen.
     */
    public function create(Citizen $citizen)
    {
        // Throws a 403 error if the user does not meet the 'create' method of the PrescriptionPolicy.
        $this->authorize('create', Prescription::class);
        $templates = config('prescription_templates', []);

        return view('prescriptions.create', compact('citizen', 'templates'));
    }

    /**
     * Stores a new prescription for a citizen.
     */
    public function store(Request $request, Citizen $citizen)
    {
        // Throws a 403 error if the user does not meet the 'create' method of the PrescriptionPolicy.
        $this->authorize('create', Prescription::class);
        /** @var User $doctor */
        $doctor = Auth::user(); // Der Arzt, der das Rezept ausstellt

        $validated = $request->validate([
            'medication' => 'required|string|max:255',
            'dosage' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        // Assign the new prescription to a variable to get its ID
        $prescription = $citizen->prescriptions()->create([
            'user_id' => $doctor->id,
            'medication' => $validated['medication'],
            'dosage' => $validated['dosage'],
            'notes' => $validated['notes'],
        ]);

        // Create the ActivityLog entry
        ActivityLog::create([
            'user_id' => $doctor->id,
            'log_type' => 'PRESCRIPTION',
            'action' => 'CREATED',
            'target_id' => $prescription->id,
            'description' => "Prescription for '{$prescription->medication}' issued to patient '{$citizen->name}'.",
        ]);

        // --- BENACHRICHTIGUNG VIA EVENT ---
        PotentiallyNotifiableActionOccurred::dispatch(
            action: 'PrescriptionController@store', // Action Name
            triggeringUser: $citizen,               // Der Patient (als triggering "User", auch wenn kein User-Objekt)
            relatedModel: $prescription,            // Das erstellte Rezept (related model)
            actorUser: $doctor                      // Der Arzt, der ausstellt (actor)
        );
        // ---------------------------------

        // Erfolgsmeldung entfernt
        return redirect()->route('citizens.show', [$citizen, 'tab' => 'prescriptions']);
    }

    /**
     * Deletes (cancels) a prescription.
     */
    public function destroy(Prescription $prescription)
    {
        // Throws a 403 error if the user does not meet the 'delete' method.
        $this->authorize('delete', $prescription);
        /** @var User $doctor */
        $doctor = Auth::user(); // Der Arzt, der storniert
        $citizen = $prescription->citizen; // Den Patienten holen

        // Store details for the log and event before deleting
        $citizenName = $citizen->name;
        $medication = $prescription->medication;
        $prescriptionId = $prescription->id;
        $prescriptionData = $prescription->toArray(); // Daten für das Event speichern

        $prescription->delete();

        // Create the ActivityLog entry
        ActivityLog::create([
            'user_id' => $doctor->id,
            'log_type' => 'PRESCRIPTION',
            'action' => 'DELETED',
            'target_id' => $prescriptionId,
            'description' => "Prescription for '{$medication}' for patient '{$citizenName}' was canceled.",
        ]);

        // --- BENACHRICHTIGUNG VIA EVENT ---
        PotentiallyNotifiableActionOccurred::dispatch(
            action: 'PrescriptionController@destroy',      // Action Name
            triggeringUser: $citizen,                      // Der Patient (als triggering "User")
            relatedModel: (object) $prescriptionData,      // Gelöschte Daten als Objekt übergeben
            actorUser: $doctor,                            // Der Arzt, der storniert (actor)
            additionalData: ['name' => $medication, 'citizen_name' => $citizenName] // Zusätzliche Daten für den Listener
        );
        // ---------------------------------

        // Erfolgsmeldung entfernt
        return back();
    }
}
