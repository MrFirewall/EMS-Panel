<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog; // Wichtig: Model importieren
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DutyStatusController extends Controller
{
    public function toggle(Request $request)
    {
        $user = Auth::user();
        
        // Status umkehren
        $user->on_duty = !$user->on_duty;
        $user->save();

        // NEU: Aktivität basierend auf dem neuen Status loggen
        if ($user->on_duty) {
            // Der Benutzer hat den Dienst angetreten
            ActivityLog::create([
                'user_id' => $user->id,
                'log_type' => 'DUTY_START',
                'action' => 'TOGGLED',
                'description' => 'Benutzer hat den Dienst angetreten.',
            ]);
            $status_text = 'Im Dienst';
        } else {
            // Der Benutzer hat den Dienst beendet
            ActivityLog::create([
                'user_id' => $user->id,
                'log_type' => 'DUTY_END',
                'action' => 'TOGGLED',
                'description' => 'Benutzer hat den Dienst beendet.',
            ]);
            $status_text = 'Außer Dienst';
        }

        // Erfolgreiche Antwort mit neuem Status zurückgeben
        return response()->json([
            'success' => true,
            'new_status' => $user->on_duty,
            'status_text' => $status_text
        ]);
    }
}
