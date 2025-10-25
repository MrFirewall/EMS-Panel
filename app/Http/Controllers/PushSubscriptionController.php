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
}