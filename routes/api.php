<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\NotificationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Routen, die eine Authentifizierung über das Web-Session-Cookie benötigen
// (Da diese Routen per AJAX von der Frontend-Web-Anwendung aufgerufen werden)
Route::middleware('auth.cfx')->group(function () {
    
    // Benachrichtigungen abrufen (für das Navbar-Dropdown)
    // Wird alle 60 Sekunden vom Frontend aufgerufen
    Route::get('/notifications/fetch', [NotificationController::class, 'fetch'])
        ->name('api.notifications.fetch');

    // Prüfungsversuch flaggen
    Route::post('/exams/flag/{uuid}', [ExamController::class, 'flag'])
        ->name('api.exams.flag');
});

// Beispiel für eine öffentliche API-Route (wenn benötigt)
// Route::get('/status', fn() => response()->json(['status' => 'ok']));
