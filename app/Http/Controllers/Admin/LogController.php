<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class LogController extends Controller
{
    /**
     * Schützt den Controller mit der 'logs.view' Berechtigung.
     */
    public function __construct()
    {
        // Nur Benutzer mit der Berechtigung 'logs.view' können diese Seite aufrufen.
        $this->middleware('can:logs.view')->only('index');
    }

    /**
     * Zeigt das Audit-Log an.
     */
    public function index()
    {
        // Lädt die Logs und den zugehörigen Benutzer (user) für die Anzeige
        $logs = ActivityLog::with('user')->latest()->paginate(20);

        return view('admin.logs.index', compact('logs'));
    }
}