<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog; // Add this
use App\Models\Citizen;
use App\Models\Prescription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Add this

class PrescriptionController extends Controller
{
    /**
     * Shows the form for creating a new prescription for a citizen.
     */
    public function create(Citizen $citizen)
    {
        // Throws a 403 error if the user does not meet the 'create' method of the PrescriptionPolicy.
        $this->authorize('create', Prescription::class);
        
        return view('prescriptions.create', compact('citizen'));
    }

    /**
     * Stores a new prescription for a citizen.
     */
    public function store(Request $request, Citizen $citizen)
    {
        // Throws a 403 error if the user does not meet the 'create' method of the PrescriptionPolicy.
        $this->authorize('create', Prescription::class);

        $validated = $request->validate([
            'medication' => 'required|string|max:255',
            'dosage' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        // Assign the new prescription to a variable to get its ID
        $prescription = $citizen->prescriptions()->create([
            'user_id' => Auth::id(),
            'medication' => $validated['medication'],
            'dosage' => $validated['dosage'],
            'notes' => $validated['notes'],
        ]);

        // Create the ActivityLog entry
        ActivityLog::create([
            'user_id' => Auth::id(),
            'log_type' => 'PRESCRIPTION',
            'action' => 'CREATED',
            'target_id' => $prescription->id,
            'description' => "Prescription for '{$prescription->medication}' issued to patient '{$citizen->name}'.",
        ]);

        return redirect()->route('citizens.show', [$citizen, 'tab' => 'prescriptions'])->with('success', 'Prescription successfully issued.');
    }

    /**
     * Deletes (cancels) a prescription.
     */
    public function destroy(Prescription $prescription)
    {
        // Throws a 403 error if the user does not meet the 'delete' method.
        $this->authorize('delete', $prescription);

        // Store details for the log before deleting
        $citizenName = $prescription->citizen->name;
        $medication = $prescription->medication;
        $prescriptionId = $prescription->id;

        $prescription->delete();

        // Create the ActivityLog entry
        ActivityLog::create([
            'user_id' => Auth::id(),
            'log_type' => 'PRESCRIPTION',
            'action' => 'DELETED',
            'target_id' => $prescriptionId,
            'description' => "Prescription for '{$medication}' for patient '{$citizenName}' was canceled.",
        ]);

        return back()->with('success', 'Prescription was canceled.');
    }
}