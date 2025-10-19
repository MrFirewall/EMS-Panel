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
use App\Http\Controllers\Admin\TrainingAssignmentController;
use App\Http\Controllers\TrainingModuleController; // Korrekter Import
use App\Http\Controllers\DutyStatusController;
use App\Http\Controllers\CitizenController;
use App\Http\Controllers\PrescriptionController;
use App\Http\Controllers\ExamController; // NEU
use App\Http\Controllers\NotificationController; // NEU
use Lab404\Impersonate\Controllers\ImpersonateController;

/*
|--------------------------------------------------------------------------
| Öffentliche Routen & Authentifizierung
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

// Login & Logout
Route::get('login', fn() => redirect()->route('login.cfx'))->name('login');
Route::get('login/cfx', [LoginController::class, 'redirectToCfx'])->name('login.cfx');
Route::get('login/cfx/callback', [LoginController::class, 'handleCfxCallback']);
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

// ID-Check
Route::get('/check-id', [LoginController::class, 'showCheckIdPage'])->name('check-id.show');
Route::get('/check-id/start', [LoginController::class, 'startCheckIdFlow'])->name('check-id.start');

// Impersonate Routes
Route::middleware(['web', 'auth'])->group(function() {
    Route::get('/impersonate/take/{id}/{guardName?}', [ImpersonateController::class, 'take'])->name('impersonate');
    Route::get('/impersonate/leave', [ImpersonateController::class, 'leave'])->name('impersonate.leave');
});

/*
|--------------------------------------------------------------------------
| Authentifizierte Benutzer-Routen
|--------------------------------------------------------------------------
*/

Route::middleware('auth.cfx')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');

    // Standard-Ressourcen
    Route::resource('reports', ReportController::class);
    Route::resource('citizens', CitizenController::class);

    // Dienststatus
    Route::post('/duty-status/toggle', [DutyStatusController::class, 'toggle'])->name('duty.toggle');

    // Urlaubsanträge
    Route::get('vacations/request', [VacationController::class, 'create'])->name('vacations.create');
    Route::post('vacations', [VacationController::class, 'store'])->name('vacations.store');
    
    // Rezepte
    Route::get('citizens/{citizen}/prescriptions/create', [PrescriptionController::class, 'create'])->name('prescriptions.create');
    Route::post('citizens/{citizen}/prescriptions', [PrescriptionController::class, 'store'])->name('prescriptions.store');
    
    // Formular-System (Bewertungen & Anträge)
    Route::prefix('forms/evaluations')->name('forms.evaluations.')->group(function () {
        Route::get('/', [EvaluationController::class, 'index'])->name('index');
        Route::get('azubi', [EvaluationController::class, 'azubi'])->name('azubi');
        Route::get('praktikant', [EvaluationController::class, 'praktikant'])->name('praktikant');
        Route::get('leitstelle', [EvaluationController::class, 'leitstelle'])->name('leitstelle');
        Route::get('mitarbeiter', [EvaluationController::class, 'mitarbeiter'])->name('mitarbeiter');
        Route::post('/', [EvaluationController::class, 'store'])->name('store');
        Route::get('modul-anmeldung', [EvaluationController::class, 'modulAnmeldung'])->name('modulAnmeldung');
        Route::get('pruefung-anmeldung', [EvaluationController::class, 'pruefungsAnmeldung'])->name('pruefungsAnmeldung');
    });

    // NEU: Prüfung ablegen (öffentlich zugänglich für den Prüfling mit dem Link)
    Route::get('/exams/take/{uuid}', [ExamController::class, 'take'])->name('exams.take');
    Route::post('/exams/submit/{uuid}', [ExamController::class, 'submit'])->name('exams.submit');
    Route::get('/exams/result/{uuid}', [ExamController::class, 'result'])->name('exams.result');
    Route::post('/exams/flag/{attempt:uuid}', [ExamController::class, 'flagAttempt'])->name('exams.flag');
    
    // NEU: API-Routen für Frontend-Interaktionen
    Route::prefix('api')->name('api.')->group(function () {
        Route::post('/exams/flag/{uuid}', [ExamController::class, 'flag'])->name('exams.flag');
        Route::get('/notifications', [NotificationController::class, 'fetch'])->name('notifications.fetch');
    });


    Route::resource('modules', TrainingModuleController::class); // NEU: Hier korrekt platziert
});


/*
|--------------------------------------------------------------------------
| Admin-Bereich
|--------------------------------------------------------------------------
*/

Route::middleware(['auth.cfx', 'can:admin.access'])->prefix('admin')->name('admin.')->group(function () {
    
    // Management-Ressourcen
    Route::resource('announcements', AnnouncementController::class);
    Route::resource('users', UserController::class)->except(['destroy']);
    Route::resource('roles', RoleController::class)->except(['create', 'edit', 'show']);
    Route::resource('permissions', PermissionController::class)->except(['show']);

    // Spezifische Admin-Aktionen
    Route::post('users/{user}/records', [UserController::class, 'addRecord'])->name('users.records.store');
    Route::get('logs', [LogController::class, 'index'])->name('logs.index');

    // Urlaubsverwaltung
    Route::get('vacations', [VacationController::class, 'index'])->name('vacations.index');
    Route::patch('vacations/{vacation}/status', [VacationController::class, 'updateStatus'])->name('vacations.update.status');

    // Detailansicht für Formulare (Bewertungen & Anträge)
    Route::get('forms/evaluations/{evaluation}', [EvaluationController::class, 'show'])->name('forms.evaluations.show');

    // NEU: Aktionen für das Ausbildungsmodul
    Route::post('training/assign/{user}/{module}/{evaluation}', [TrainingAssignmentController::class, 'assign'])->name('training.assign');
    Route::post('exams/generate-link', [ExamController::class, 'generateLink'])->name('exams.generateLink');

    // NEU: Prüfungs-Verwaltung (CRUD der Prüfungsdefinitionen)
    Route::resource('exams', \App\Http\Controllers\Admin\ExamController::class);
    
    // NEU: Prüfungsversuch-Verwaltung (ExamAttempt Management)
    Route::prefix('attempts')->name('exams.')->group(function () {
        // Übersicht über alle Prüfungsversuche
        Route::get('/', [\App\Http\Controllers\Admin\ExamController::class, 'attemptsIndex'])->name('attempts.index');
        
        // Setzt den Prüfungsversuch zurück (löscht Antworten und setzt Status auf 'in_progress')
        Route::post('/{attempt}/reset', [\App\Http\Controllers\Admin\ExamController::class, 'resetAttempt'])->name('reset.attempt');
        
        // Manuelle Bewertung durch Admin
        Route::post('/{attempt}/evaluate', [\App\Http\Controllers\Admin\ExamController::class, 'setEvaluated'])->name('set.evaluated');
        
        // Link erneut senden
        Route::post('/{attempt}/send-link', [\App\Http\Controllers\Admin\ExamController::class, 'sendLink'])->name('send.link');
    });
});
