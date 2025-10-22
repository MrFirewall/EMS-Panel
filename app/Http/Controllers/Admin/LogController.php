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
     * WICHTIG: Holt alle Logs. Bei sehr großen Datenmengen (10000+) Server-Side Processing verwenden.
     */
    public function index()
    {
        // Lädt die Logs und den zugehörigen Benutzer (user) für die Anzeige
        // Wir holen alle Logs, da DataTables die Paginierung clientseitig übernimmt.
        $logs = ActivityLog::with('user')->latest()->get();

        return view('admin.logs.index', compact('logs'));
    }
}
