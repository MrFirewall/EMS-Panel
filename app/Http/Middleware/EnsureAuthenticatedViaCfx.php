<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EnsureAuthenticatedViaCfx
{
    public function handle(Request $request, Closure $next)
    {
        Log::debug('--- [Auth Debug] Middleware gestartet für: ' . $request->fullUrl() . ' ---');

        if (!Auth::check()) {
            Log::debug('[Auth Debug] Auth::check() ist FALSCH. Leite zu Login weiter.');
            return redirect()->route('login');
        }

        Log::debug('[Auth Debug] Auth::check() ist WAHR. User ID: ' . Auth::id());
        
        // ========================================================================
        // NEUE, ROBUSTE PRÜFUNG:
        // Wir prüfen direkt in der Session, ob der Schlüssel des Impersonators
        // gesetzt ist. Dies ist die zuverlässigste Methode.
        // ========================================================================
        $impersonatorId = session(app('impersonate')->getSessionKey());
        $isImpersonating = $impersonatorId !== null;
        
        Log::debug('[Auth Debug] Session-Schlüssel für Impersonator vorhanden? ' . ($isImpersonating ? 'JA (ID: ' . $impersonatorId . ')' : 'NEIN'));
        
        if ($isImpersonating) {
            Log::debug('[Auth Debug] Prüfung 1 (Session Key) erfolgreich. Lasse Request durch.');
            return $next($request);
        }

        // PRÜFUNG 2: Ist es ein normaler, über CFX authentifizierter Login?
        $isCfxAuthenticated = session('is_cfx_authenticated') === true;
        Log::debug('[Auth Debug] session(\'is_cfx_authenticated\')? ' . ($isCfxAuthenticated ? 'JA' : 'NEIN'));
        
        if ($isCfxAuthenticated) {
            Log::debug('[Auth Debug] Prüfung 2 erfolgreich. Lasse Request durch.');
            return $next($request);
        }

        // FALLBACK: Wenn BEIDE Prüfungen fehlschlagen, ist die Session ungültig.
        Log::warning('[Auth Debug] FALLBACK! Keine der Prüfungen war erfolgreich. User wird ausgeloggt.');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('login')->with('error', 'Ihre Sitzung ist abgelaufen. Bitte erneut anmelden.');
    }
}

