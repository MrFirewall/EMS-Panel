<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller {
    public function store(Request $request) {
        $request->validate([
            'endpoint' => 'required|url',
            'keys.p256dh' => 'required',
            'keys.auth' => 'required',
        ]);

        auth()->user()->pushSubscriptions()->updateOrCreate(
            ['endpoint' => $request->endpoint],
            [
                'public_key' => $request->input('keys.p256dh'),
                'auth_token' => $request->input('keys.auth'),
            ]
        );
        return response()->json(['success' => true], 201);
    }
    
    public function destroy(Request $request)
        {
            $request->validate([
                'endpoint' => 'required|url', // Endpoint ist der eindeutige Schlüssel
            ]);

            // Finde und lösche das Abo anhand des Endpoints *nur für diesen User*
            $deleted = auth()->user()
                             ->pushSubscriptions() // Nutzt die Relation im User Model
                             ->where('endpoint', $request->endpoint)
                             ->delete();

            if ($deleted) {
                return response()->json(['success' => true], 200);
            } else {
                // Abo wurde nicht gefunden (vielleicht schon gelöscht?)
                return response()->json(['success' => false, 'message' => 'Subscription not found.'], 404);
            }
        }
}