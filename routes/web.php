<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\VacationController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\UserController;
use Lab404\Impersonate\Controllers\ImpersonateController;
use App\Http\Controllers\DutyStatusController;
use App\Http\Controllers\CitizenController;
use App\Http\Controllers\PrescriptionController;
use App\Http\Controllers\TrainingModuleController;

/*
|--------------------------------------------------------------------------
| Öffentliche Routen & Authentifizierung
|--------------------------------------------------------------------------
|
| Diese Routen sind für alle Besucher zugänglich.
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Login & Logout
Route::get('login', fn() => redirect()->route('login.cfx'))->name('login');
Route::get('login/cfx', [LoginController::class, 'redirectToCfx'])->name('login.cfx');
Route::get('login/cfx/callback', [LoginController::class, 'handleCfxCallback']);
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

// ID-Check (falls benötigt)
Route::get('/check-id', [LoginController::class, 'showCheckIdPage'])->name('check-id.show');
Route::get('/check-id/start', [LoginController::class, 'startCheckIdFlow'])->name('check-id.start');

Route::middleware(['web', 'auth'])->group(function() {
    Route::get('/impersonate/take/{id}/{guardName?}', [ImpersonateController::class, 'take'])
        ->name('impersonate');
    Route::get('/impersonate/leave', [ImpersonateController::class, 'leave'])
        ->name('impersonate.leave');
});
/*
|--------------------------------------------------------------------------
| Authentifizierte Benutzer-Routen
|--------------------------------------------------------------------------
|
| Diese Routen erfordern, dass der Benutzer eingeloggt ist.
|
*/

Route::middleware('auth.cfx')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');

    // Standard-Ressourcen für eingeloggte Benutzer
    Route::resource('reports', ReportController::class);
    // Dienststatus umschalten
    Route::post('/duty-status/toggle', [DutyStatusController::class, 'toggle'])->name('duty.toggle');
    // Mitarbeiter-Aktionen für Urlaub
    Route::get('vacations/request', [VacationController::class, 'create'])->name('vacations.create');
    Route::post('vacations', [VacationController::class, 'store'])->name('vacations.store');

    Route::resource('modules', TrainingModuleController::class);

    // Bürgerakten verwalten
    Route::resource('citizens', CitizenController::class);
    Route::get('citizens/{citizen}/prescriptions/create', [PrescriptionController::class, 'create'])->name('prescriptions.create');
    Route::post('citizens/{citizen}/prescriptions', [PrescriptionController::class, 'store'])->name('prescriptions.store');
    // Mitarbeiter-Aktionen für Bewertungen
    Route::prefix('forms/evaluations')->name('forms.evaluations.')->group(function () {
        Route::get('/', [EvaluationController::class, 'index'])->name('index');
        Route::get('azubi', [EvaluationController::class, 'azubi'])->name('azubi');
        Route::get('praktikant', [EvaluationController::class, 'praktikant'])->name('praktikant');
        Route::get('leitstelle', [EvaluationController::class, 'leitstelle'])->name('leitstelle');
        Route::get('mitarbeiter', [EvaluationController::class, 'mitarbeiter'])->name('mitarbeiter');
        Route::post('/', [EvaluationController::class, 'store'])->name('store');
        Route::get('modul-anmeldung', [EvaluationController::class, 'modulAnmeldung'])->name('modulAnmeldung');
        Route::get('pruefung-anmeldung', [EvaluationController::class, 'pruefungsAnmeldung'])->name('pruefungsAnmeldung');
        // Die "show"-Route für Admins ist unten im Admin-Bereich definiert.
    });
});


/*
|--------------------------------------------------------------------------
| Admin-Bereich
|--------------------------------------------------------------------------
|
| Alle Routen hier sind durch 'auth' und 'can:admin.access' geschützt.
| Die granulare Rechteprüfung findet in den jeweiligen Controllern statt.
|
*/

Route::middleware(['auth.cfx', 'can:admin.access'])->prefix('admin')->name('admin.')->group(function () {
    
    // Management-Ressourcen
    Route::resource('announcements', AnnouncementController::class);
    Route::resource('users', UserController::class)->except(['destroy']); // 'destroy' ggf. später hinzufügen
    Route::resource('roles', RoleController::class)->except(['create', 'edit', 'show']);
    Route::resource('permissions', PermissionController::class)->except(['show']);

    // Spezifische Admin-Aktionen
    Route::post('users/{user}/records', [UserController::class, 'addRecord'])->name('users.records.store');
    
    // Log-Ansicht
    Route::get('logs', [LogController::class, 'index'])->name('logs.index');

    // Urlaubsverwaltung
    Route::get('vacations', [VacationController::class, 'index'])->name('vacations.index');
    Route::patch('vacations/{vacation}/status', [VacationController::class, 'updateStatus'])->name('vacations.update.status');

    // Detailansicht für Bewertungen (nur für Admins hier)
    Route::get('forms/evaluations/{evaluation}/show', [EvaluationController::class, 'show'])->name('forms.evaluations.show');


});