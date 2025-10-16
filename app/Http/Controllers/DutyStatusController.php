<?php

namespace App\Http\Controllers;

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

        // Erfolgreiche Antwort mit neuem Status zurückgeben
        return response()->json([
            'success' => true,
            'new_status' => $user->on_duty,
            'status_text' => $user->on_duty ? 'Im Dienst' : 'Außer Dienst'
        ]);
    }
}