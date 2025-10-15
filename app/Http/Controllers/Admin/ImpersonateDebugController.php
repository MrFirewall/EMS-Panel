<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonateDebugController extends Controller
{
    /**
     * Führt die Impersonation durch und debuggt die Session.
     */
    public function take($id)
    {
        $user_to_impersonate = User::findOrFail($id);

        // Der Benutzer, der die Aktion ausführt (der Admin)
        $impersonator = Auth::user();

        // Führe die Impersonation durch
        Auth::user()->impersonate($user_to_impersonate);
        
        // ================================================================= //
        //                  DER ENTSCHEIDENDE DEBUG-PUNKT                    //
        // ================================================================= //
        // Wir stoppen alles, NACHDEM der Benutzer gewechselt wurde,
        // aber BEVOR die Weiterleitung stattfindet.
        dd([
            'AKTION' => 'Impersonation wurde ausgeführt.',
            'Admin (Impersonator)' => $impersonator->name,
            'Ziel-User' => $user_to_impersonate->name,
            'Aktuell eingeloggter User (sollte Ziel-User sein)' => Auth::user()->name,
            'SESSION NACH DER AKTION' => session()->all(),
        ]);
        // ================================================================= //

        return redirect()->route('dashboard');
    }

    /**
     * Beendet die Impersonation.
     */
    public function leave()
    {
        Auth::user()->leaveImpersonation();

        return redirect()->route('admin.users.index');
    }
}