<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Events\PotentiallyNotifiableActionOccurred; // Event hinzufügen
use App\Models\User; // User hinzufügen für Typ-Hinting

class DutyStatusController extends Controller
{
    public function toggle(Request $request)
    {
        /** @var User $user */ // Type hint for static analysis
        $user = Auth::user();

        // Status umkehren
        $user->on_duty = !$user->on_duty;
        $user->save();

        if ($user->on_duty) {
            // Der Benutzer hat den Dienst angetreten
            ActivityLog::create([
                'user_id' => $user->id,
                'log_type' => 'DUTY_START',
                'action' => 'TOGGLED',
                'description' => 'Benutzer hat den Dienst angetreten.',
            ]);
            $status_text = 'Im Dienst';
            $actionIdentifier = 'DutyStatusController@toggle.on_duty'; // Spezifischer Identifier
        } else {
            // Der Benutzer hat den Dienst beendet
            ActivityLog::create([
                'user_id' => $user->id,
                'log_type' => 'DUTY_END',
                'action' => 'TOGGLED',
                'description' => 'Benutzer hat den Dienst beendet.',
            ]);
            $status_text = 'Außer Dienst';
            $actionIdentifier = 'DutyStatusController@toggle.off_duty'; // Spezifischer Identifier
        }

        // --- BENACHRICHTIGUNG VIA EVENT ---
        PotentiallyNotifiableActionOccurred::dispatch(
            $actionIdentifier, // Spezifischer Name für an/abmelden
            $user,             // Der Benutzer, der den Status ändert
            $user,             // Das zugehörige Modell
            $user              // Der auslösende Benutzer ist hier derselbe
        );
        // ---------------------------------

        // Erfolgreiche Antwort mit neuem Status zurückgeben
        return response()->json([
            'success' => true,
            'new_status' => $user->on_duty,
            'status_text' => $status_text
        ]);
    }
}
