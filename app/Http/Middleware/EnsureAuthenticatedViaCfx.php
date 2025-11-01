<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
// Log facade can be removed if not used elsewhere in the file, but it's fine to leave it.
use Illuminate\Support\Facades\Log; 

class EnsureAuthenticatedViaCfx
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if the user is authenticated at all.
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // ========================================================================
        // NEU: Prüfen, ob die Sitzung gesperrt ist (Lockscreen)
        // ========================================================================
        // Verhindert eine Endlosschleife, indem Lockscreen- und Login-Routen erlaubt werden
        if (session('is_locked') === true && !$request->routeIs('lockscreen') && !$request->routeIs('login.*')) {
            return redirect()->route('lockscreen');
        }

        // ========================================================================
        // Check if the user is currently impersonating someone else.
        // ========================================================================
        $impersonatorId = session(app('impersonate')->getSessionKey());
        $isImpersonating = $impersonatorId !== null;
        
        if ($isImpersonating) {
            // If impersonating, allow the request.
            return $next($request);
        }

        // ========================================================================
        // Check if the user was authenticated via the standard CFX login flow.
        // ========================================================================
        $isCfxAuthenticated = session('is_cfx_authenticated') === true;
        
        if ($isCfxAuthenticated) {
            // If authenticated via CFX, allow the request.
            return $next($request);
        }

        // ========================================================================
        // FALLBACK: Session ist abgelaufen oder ungültig.
        // NEUE LOGIK: Zum Lockscreen umleiten statt zum Login.
        // ========================================================================
        
        // 1. Benutzerdaten holen, BEVOR wir ihn ausloggen
        $user = Auth::user(); 
        
        // 2. Ausloggen und Session invalidieren
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        // 3. Minimale Daten für den Lockscreen in die NEUE Session speichern
        $request->session()->put('lockscreen_name', $user->name ?? 'Gesperrter Benutzer');
        $request->session()->put('lockscreen_avatar', $user->avatar ?? null);
        $request->session()->put('is_locked', true); // Als gesperrt markieren

        // 4. Zum Lockscreen umleiten
        return redirect()->route('lockscreen');
    }
}
