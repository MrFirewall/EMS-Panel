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
        // Check if the user is currently impersonating someone else.
        // This checks the session key used by the impersonation library.
        // ========================================================================
        $impersonatorId = session(app('impersonate')->getSessionKey());
        $isImpersonating = $impersonatorId !== null;
        
        if ($isImpersonating) {
            // If impersonating, allow the request.
            return $next($request);
        }

        // ========================================================================
        // Check if the user was authenticated via the standard CFX login flow.
        // This relies on a session variable set during the CFX callback.
        // ========================================================================
        $isCfxAuthenticated = session('is_cfx_authenticated') === true;
        
        if ($isCfxAuthenticated) {
            // If authenticated via CFX, allow the request.
            return $next($request);
        }

        // ========================================================================
        // FALLBACK: If neither impersonating nor authenticated via CFX,
        // the session is considered invalid or expired for protected routes.
        // Log the user out and redirect to login.
        // ========================================================================
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('login')->with('error', 'Ihre Sitzung ist abgelaufen. Bitte erneut anmelden.');
    }
}
