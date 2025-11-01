<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LockscreenController;
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
use App\Http\Controllers\TrainingModuleController;
use App\Http\Controllers\DutyStatusController;
use App\Http\Controllers\CitizenController;
use App\Http\Controllers\PrescriptionController;
use App\Http\Controllers\NotificationController;
use Lab404\Impersonate\Controllers\ImpersonateController;
use App\Models\User;
use App\Notifications\GeneralNotification;
use Illuminate\Support\Facades\Auth; 
use App\Http\Controllers\Admin\NotificationRuleController;
use App\Http\Controllers\ExamAttemptController;
use App\Http\Controllers\Admin\ExamController as AdminExamController;
use App\Http\Controllers\Admin\ExamAttemptController as AdminExamAttemptController;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;


use App\Http\Controllers\PushSubscriptionController;
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
Route::get('/lockscreen', [LockscreenController::class, 'show'])->name('lockscreen');
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
    Route::get('/lock', [LockscreenController::class, 'lock'])->name('lock');
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
    
    Route::resource('modules', TrainingModuleController::class);

    /*
    |--------------------------------------------------------------------------
    | NEU: PRÜFUNGS-ROUTEN (FÜR PRÜFLINGE)
    |--------------------------------------------------------------------------
    */
    // Prüfung ablegen (Nutzt RMB mit 'uuid' dank Model-Definition)
    Route::get('/exams/take/{attempt:uuid}', [ExamAttemptController::class, 'show'])->name('exams.take');
    // Prüfung einreichen
    Route::post('/exams/submit/{attempt:uuid}', [ExamAttemptController::class, 'update'])->name('exams.submit');
    // Generische Bestätigungsseite nach Submit
    Route::get('/exams/submitted', [ExamAttemptController::class, 'submitted'])->name('exams.submitted');

    /*
    |--------------------------------------------------------------------------
    | Benachrichtigungs-Archiv und Aktionen
    |--------------------------------------------------------------------------
    */
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllRead'])->name('markAllRead');
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('markAsRead');
        Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-mark-read', [NotificationController::class, 'bulkMarkRead'])->name('bulkMarkRead');
        Route::post('/bulk-destroy', [NotificationController::class, 'bulkDestroy'])->name('bulkDestroy');
        Route::post('/clear-read', [NotificationController::class, 'clearRead'])->name('clearRead');
    });

    Route::post('/push-subscribe', [PushSubscriptionController::class, 'store'])->middleware('auth')->name('push.subscribe');
    Route::post('/push-unsubscribe', [PushSubscriptionController::class, 'destroy'])->middleware('auth')->name('push.unsubscribe');
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
    Route::post('roles/ranks/reorder', [RoleController::class, 'updateRankOrder'])->name('roles.ranks.reorder')->middleware('can:roles.edit');
    Route::post('departments', [RoleController::class, 'storeDepartment'])->name('departments.store')->middleware('can:roles.create');
    Route::put('departments/{department}', [RoleController::class, 'updateDepartment'])->name('departments.update')->middleware('can:roles.edit');
    Route::delete('departments/{department}', [RoleController::class, 'destroyDepartment'])->name('departments.destroy')->middleware('can:roles.delete');
    Route::resource('permissions', PermissionController::class)->except(['show']);

    // Spezifische Admin-Aktionen
    Route::post('users/{user}/records', [UserController::class, 'addRecord'])->name('users.records.store');
    Route::get('logs', [LogController::class, 'index'])->name('logs.index');

    // Urlaubsverwaltung
    Route::get('vacations', [VacationController::class, 'index'])->name('vacations.index');
    Route::patch('vacations/{vacation}/status', [VacationController::class, 'updateStatus'])->name('vacations.update.status');

    // Detailansicht für Formulare (Bewertungen & Anträge)
    Route::get('forms/evaluations/{evaluation}', [EvaluationController::class, 'show'])->name('forms.evaluations.show');

    // Aktionen für das Ausbildungsmodul
    Route::post('training/assign/{user}/{module}/{evaluation}', [TrainingAssignmentController::class, 'assign'])->name('training.assign');

    // Detailansicht für Formulare (Bewertungen & Anträge)
    Route::get('forms/evaluations/{evaluation}', [EvaluationController::class, 'show'])->name('forms.evaluations.show');

    // NEU: Route zum Löschen von Anträgen/Bewertungen
    Route::delete('forms/evaluations/{evaluation}', [EvaluationController::class, 'destroy'])->name('forms.evaluations.destroy');

    // Aktionen für das Ausbildungsmodul
    Route::post('training/assign/{user}/{module}/{evaluation}', [TrainingAssignmentController::class, 'assign'])->name('training.assign');

    
    /*
    |--------------------------------------------------------------------------
    | NEU: PRÜFUNGS-MANAGEMENT (ADMIN)
    |--------------------------------------------------------------------------
    */
    
    // 1. CRUD für Exam-Vorlagen (admin.exams.index, .create, .store, etc.)
    Route::resource('exams', AdminExamController::class);
    
    // 2. Management für ExamAttempt-Instanzen
    Route::prefix('attempts')->name('exams.attempts.')->group(function () {
        
        // Übersicht (admin.exams.attempts.index)
        Route::get('/', [AdminExamAttemptController::class, 'index'])->name('index');
        
        // Link generieren (Formular ist woanders, dies ist die POST-Aktion)
        // (admin.exams.attempts.store)
        Route::post('/', [AdminExamAttemptController::class, 'store'])->name('store');
        
        // Ergebnis-Detailansicht für Admin (admin.exams.attempts.show)
        Route::get('/{attempt:uuid}', [AdminExamAttemptController::class, 'show'])->name('show');
        
        // Finale Bewertung & Modulabschluss (admin.exams.attempts.update)
        Route::post('/{attempt:uuid}/finalize', [AdminExamAttemptController::class, 'update'])->name('update');
        
        // --- Zusätzliche Aktionen ---
        
        // Versuch zurücksetzen
        Route::post('/{attempt:uuid}/reset', [AdminExamAttemptController::class, 'resetAttempt'])->name('reset');
        
        // Link erneut senden/anzeigen
        Route::post('/{attempt:uuid}/send-link', [AdminExamAttemptController::class, 'sendLink'])->name('sendLink');
        
        // Manuelle Bewertung (alte Route)
        Route::post('/{attempt:uuid}/evaluate', [AdminExamAttemptController::class, 'setEvaluated'])->name('setEvaluated');

        // NEU: Löschen-Route
        Route::delete('/{attempt:uuid}', [AdminExamAttemptController::class, 'destroy'])->name('destroy');
    });

    // Benachrichtigungsregeln Verwaltung
    Route::middleware(['can:notification.rules.manage'])->resource('notification-rules', NotificationRuleController::class)->except(['show']);
    
    Route::get('/api/session-expiry', function (Request $request) {
        $lastActivity = DB::table(config('session.table'))
        ->where('user_id', Auth::id())
        ->where('id', $request->session()->getId())
        ->value('last_activity');
        
        if (!$lastActivity) {
            return response()->json(['error' => 'Session not found'], 404);
        }
        
        $expiryTimestamp = $lastActivity + (config('session.lifetime') * 60);
        
        return response()->json([
            'expiry_timestamp' => $expiryTimestamp,
        ]);
    })->name('api.session.expiry');

    Route::get('/api/session-ping', function () {
        return response()->json(['status' => 'pong']);
    })->name('api.session.ping');

});


/*
|--------------------------------------------------------------------------
| Interne API-Routen (für Frontend-AJAX)
|--------------------------------------------------------------------------
*/
Route::middleware('auth.cfx')
     ->prefix('api')
     ->group(function () {
    
    Route::get('/notifications/fetch', [NotificationController::class, 'fetch'])
        ->name('api.notifications.fetch');

    // HINWEIS: Diese Route muss evtl. angepasst werden, falls der ExamController
    // die 'flag'-Methode hatte. Sie wurde im Code nicht bereitgestellt.
    // Route::post('/exams/flag/{uuid}', [ExamController::class, 'flag'])
    //    ->name('api.exams.flag');
});


/*
|--------------------------------------------------------------------------
| TEST-ROUTE (Temporär)
|--------------------------------------------------------------------------
*/
Route::get('/test-notification', function() {
    if (!Auth::check()) {
        return 'Bitte zuerst einloggen.';
    }
    $user = Auth::user();
    
    $user->notify(new GeneralNotification(
        'Test 1: Fehler gefunden', 'fas fa-exclamation-triangle text-danger', route('dashboard')
    ));
    $user->notify(new GeneralNotification(
        'Test 2: Neue Akte erstellt', 'fas fa-file-alt text-info', route('dashboard')
    ));
    $user->notify(new GeneralNotification(
        'Test 3: Mitarbeiter angemeldet', 'fas fa-user-plus text-success', route('dashboard')
    ));
    
    return "3 Test-Benachrichtigungen an '{$user->name}' gesendet (DB & Broadcast)!";
})->middleware('auth.cfx');
