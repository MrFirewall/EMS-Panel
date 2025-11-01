<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LockscreenController extends Controller
{
    /**
     * Zeigt den Lockscreen an.
     * Holt Benutzerdaten aus der Session, die von der Middleware gesetzt wurden.
     */
    public function show(Request $request)
    {
        // Wenn der Benutzer irgendwie noch eingeloggt ist (z.B. durch "Zurück"),
        // aber die Session als "locked" markiert ist, logge ihn zur Sicherheit aus.
        if (Auth::check() && session('is_locked')) {
             Auth::logout();
             $request->session()->invalidate();
             $request->session()->regenerateToken();
             // Die Seite wird neu geladen, die Session-Werte (Name/Avatar) müssen neu gesetzt werden
             // Dies wird aber bereits von der EnsureAuthenticatedViaCfx Middleware erledigt.
             return redirect()->route('lockscreen');
        }

        // Wenn die Session "is_locked" ist (von Middleware),
        // hole die temporär gespeicherten Daten.
        if (session('is_locked')) {
            $name = $request->session()->get('lockscreen_name', 'Benutzer');
            $avatar = $request->session()->get('lockscreen_avatar');
        } else {
            // Falls jemand manuell auf /lockscreen geht, ohne ausgeloggt zu sein
            if(Auth::check()) {
                return redirect()->route('dashboard');
            }
            // Falls jemand manuell auf /lockscreen geht und ausgeloggt ist
            return redirect()->route('login');
        }

        return view('auth.lockscreen', compact('name', 'avatar'));
    }
}
