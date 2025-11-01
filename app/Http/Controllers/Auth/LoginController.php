<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request; // Request hinzugefügt
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class LoginController extends Controller
{
    /**
     * NEU: Akzeptiert Request, um "remember" zu lesen
     */
    public function redirectToCfx(Request $request)
    {
        // NEU: Speichere den "remember" Status in der Session,
        // da wir ihn nach dem CFX-Callback wieder brauchen.
        $remember = $request->has('remember');
        session(['login_remember' => $remember]);

        return Socialite::driver('cfx')->redirect();
    }

    /**
     * Verarbeitet den Login ODER den ID-Check, basierend auf der Session.
     */
    public function handleCfxCallback()
    {
        try {
            $cfxUser = Socialite::driver('cfx')->user();

            // PRÜFUNG: Wollte der User nur seine ID wissen?
            if (session('auth_flow') === 'id_check') {
                session()->forget('auth_flow'); // Session sofort wieder löschen
                
                $cfxId = $cfxUser->getId();
                $cfxName = $cfxUser->getNickname();

                return view('auth.show-id', compact('cfxId', 'cfxName'));
            }

            // --- STANDARD-LOGIN-LOGIK ---
            $user = User::where('cfx_id', $cfxUser->getId())->first();

            if ($user) {
                $user->update([
                    'cfx_name' => $cfxUser->getNickname(),
                    'avatar' => $cfxUser->getAvatar(),
                ]);

                // NEU: Hole den "remember" Status aus der Session
                // 'pull' holt den Wert und löscht den Key sofort.
                $remember = session()->pull('login_remember', false);

                // NEU: Speichere den finalen Status für den JS-Timer
                session(['is_remembered' => $remember]);
                
                // NEU: Setze das 'remember' Flag (true/false)
                Auth::login($user, $remember); 
                
                session(['is_cfx_authenticated' => true]);
                
                // NEU: Leere Lockscreen-Daten, falls vorhanden
                session()->forget('lockscreen_name');
                session()->forget('lockscreen_avatar');

                return redirect()->intended(route('dashboard'));
            } else {
                return redirect('/')->with('error', 'Dein Account wurde im System nicht gefunden. Bitte wende dich an die Personalabteilung.');
            }

        } catch (\Exception $e) {
            session()->forget('auth_flow'); // Session auch im Fehlerfall löschen
            session()->forget('login_remember'); // Auch hier löschen
            \Illuminate\Support\Facades\Log::error('Cfx.re Callback Fehler: ' . $e->getMessage());
            return redirect('/')->with('error', 'Es ist ein Fehler aufgetreten. Bitte erneut versuchen.');
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
    
    // --- METHODEN FÜR DEN ID-CHECK ---

    public function showCheckIdPage()
    {
        return view('auth.check-id');
    }

    /**
     * NEUE METHODE: Setzt die Session und startet den Redirect.
     */
    public function startCheckIdFlow()
    {
        session(['auth_flow' => 'id_check']);
        // Leitet zur Login-Route weiter, die dann "redirectToCfx" aufruft
        return redirect()->route('login.cfx');
    }
}
