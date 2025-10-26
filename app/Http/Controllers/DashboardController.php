<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Announcement;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon; // Carbon für Datumsberechnungen importieren
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    private array $rankHierarchy = [
        'chief'         => 'Chief',
        'deputy chief'  => 'Deputy Chief',
        'doctor'        => 'Doctor',
        'captain'       => 'Captain',
        'lieutenant'    => 'Lieutenant',
        'supervisor'    => 'Supervisor',
        's-emt'         => 'S-EMT (Senior EMT)',
        'paramedic'     => 'Paramedic',
        'a-emt'         => 'A-EMT (Advanced EMT)',
        'emt'           => 'EMT (Emergency Medical Technician)',
        'trainee'       => 'Trainee',
    ];

    public function index()
    {
        $user = Auth::user();

        // 1. ANKÜNDIGUNGEN
        $announcements = Announcement::where('is_active', true)->with('user')->latest()->take(5)->get();

        // 2. RANGVERTEILUNG (unverändert)
        $allUsers = User::with('roles')->get();
        $totalUsers = $allUsers->count();
        $rankDistribution = [];

        foreach ($allUsers as $u) {
            $role = $u->getRoleNames()->first();
            if ($role) {
                $rankName = $this->rankHierarchy[$role] ?? ucwords(str_replace('-', ' ', $role));
                $rankDistribution[$rankName] = ($rankDistribution[$rankName] ?? 0) + 1;
            }
        }
        
        $sortedRankDistribution = [];
        $rankHierarchyNames = array_values($this->rankHierarchy);
        foreach ($rankHierarchyNames as $name) {
             if (isset($rankDistribution[$name])) {
                 $sortedRankDistribution[$name] = $rankDistribution[$name];
                 unset($rankDistribution[$name]);
             }
        }
        $sortedRankDistribution = array_merge($sortedRankDistribution, $rankDistribution);
        
        // 3. PERSÖNLICHE ÜBERSICHT
        $lastReports = Report::where('user_id', $user->id)->latest()->take(3)->get();
            
        // 4. NEU: BERECHNUNG DER WOCHENSTUNDEN
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();
        $totalSeconds = 0;

        // Hole alle relevanten Logs für den User in der aktuellen Woche
        $dutyLogs = ActivityLog::where('user_id', $user->id)
            ->whereIn('log_type', ['DUTY_START', 'DUTY_END'])
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->orderBy('created_at', 'asc')
            ->get();

        $lastStartLog = null;

        foreach ($dutyLogs as $log) {
            if ($log->log_type === 'DUTY_START') {
                // Speichere den Start-Log
                $lastStartLog = $log;
            } elseif ($log->log_type === 'DUTY_END' && $lastStartLog) {
                // Wenn ein End-Log gefunden wird und ein Start-Log existiert, berechne die Differenz
                $totalSeconds += $lastStartLog->created_at->diffInSeconds($log->created_at);
                // Setze den Start-Log zurück, um Paare zu bilden
                $lastStartLog = null; 
            }
        }

        // Sonderfall: User ist aktuell im Dienst (letzter Log war DUTY_START)
        if ($lastStartLog) {
            $totalSeconds += $lastStartLog->created_at->diffInSeconds(Carbon::now());
        }

        // Formatiere die Gesamtsekunden in ein lesbares Format (HH:MM:SS)
        $weeklyHours = gmdate("H:i:s", $totalSeconds);

        // 5. DATEN AN DIE VIEW ÜBERGEBEN
        return view('dashboard', [
            'announcements' => $announcements,
            'rankDistribution' => $sortedRankDistribution,
            'totalUsers' => $totalUsers,
            'lastReports' => $lastReports,
            'weeklyHours' => $weeklyHours, // Hier wird die berechnete Zeit übergeben
        ]);
    }
}
