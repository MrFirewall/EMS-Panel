<?php

namespace App\Http\Controllers;

use App\Models\Citizen;
use App\Models\Prescription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PrescriptionController extends Controller
{
    /**
     * Zeigt das Formular zum Erstellen eines neuen Rezepts für einen Bürger an.
     */
    public function create(Citizen $citizen)
    {
        // Wirft einen 403-Fehler, wenn der User die 'create' Methode der PrescriptionPolicy nicht erfüllt.
        $this->authorize('create', Prescription::class);
        
        return view('prescriptions.create', compact('citizen'));
    }

    /**
     * Speichert ein neues Rezept für einen Bürger.
     */
    public function store(Request $request, Citizen $citizen)
    {
        // Wirft einen 403-Fehler, wenn der User die 'create' Methode der PrescriptionPolicy nicht erfüllt.
        $this->authorize('create', Prescription::class);

        $validated = $request->validate([
            'medication' => 'required|string|max:255',
            'dosage' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $citizen->prescriptions()->create([
            'user_id' => Auth::id(),
            'medication' => $validated['medication'],
            'dosage' => $validated['dosage'],
            'notes' => $validated['notes'],
        ]);

        return redirect()->route('citizens.show', [$citizen, 'tab' => 'prescriptions'])->with('success', 'Rezept erfolgreich ausgestellt.');
    }

    /**
     * Löscht (storniert) ein Rezept.
     */
    public function destroy(Prescription $prescription)
    {
        // Wirft einen 403-Fehler, wenn der User die 'delete' Methode nicht erfüllt.
        $this->authorize('delete', $prescription);

        $prescription->delete();

        return back()->with('success', 'Rezept wurde storniert.');
    }
}