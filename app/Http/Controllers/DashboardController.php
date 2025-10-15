<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Report;
use App\Models\User; // Sicherstellen, dass das User-Model importiert ist
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role; // Optional: Wenn du Rollen-Namen übersetzen willst
use App\Models\ActivityLog;
class DashboardController extends Controller
{
    // Definiere die Hierarchie, um die korrekte Sortierung in der Ansicht zu gewährleisten (optional)
    private array $rankHierarchy = [
        'ems-director' => 'Direktor',
        'assistant-ems-director' => 'Co. Direktor',
        'instructor' => 'Instruktor',
        'emergency-doctor' => 'Notarzt',
        'paramedic' => 'Rettungssanitäter',
        'emt' => 'Notfallsanitäter',
        'emt-trainee' => 'Azubi (EMT)', // Geändert für eine bessere Darstellung
        'praktikant' => 'Praktikant',
    ];

    public function index()
    {
        // 1. Die 5 neusten Ankündigungen holen
        $announcements = Announcement::where('is_active', true)->with('user')->latest()->take(5)->get();

        // 2. Die Anzahl der Berichte des eingeloggten Benutzers zählen
        $reportCount = Report::where('user_id', Auth::id())->count();

        // 3. Rangverteilung ermitteln (Annahme: Ein Benutzer hat maximal EINE Hauptrolle, die seinem Rang entspricht)
        
        $users = User::all(); // Alle Benutzer holen
        $rankDistribution = [];
        
        // Gesamtanzahl der Benutzer
        $totalUsers = $users->count();

        // Rollen zählen und mappen
        foreach ($users as $user) {
            // Dies ist ein Platzhalter, da die Methode zur Abfrage der Hauptrolle variieren kann.
            // Angenommen, du hast eine Methode, die die aktuelle Rolle zurückgibt:
            $roleNames = $user->getRoleNames(); // Gibt eine Collection oder Array von Rollennamen zurück (spatie/laravel-permission)
            
            if ($roleNames->isNotEmpty()) {
                // Wir nehmen die erste Rolle als Hauptrolle für das Zählen
                $primaryRoleSlug = $roleNames->first(); 
                
                // Rollen-Slug in einen lesbaren Namen umwandeln (wie in der $rankHierarchy definiert)
                $rankName = $this->rankHierarchy[$primaryRoleSlug] ?? ucwords(str_replace('-', ' ', $primaryRoleSlug));
                
                $rankDistribution[$rankName] = ($rankDistribution[$rankName] ?? 0) + 1;
            }
        }
        
        // Optional: Sortiere die Rangverteilung nach der definierten Hierarchie, falls nötig.
        // Dafür müsste man die Ränge aus $rankHierarchy als Basis nehmen
        $sortedRankDistribution = [];
        foreach ($this->rankHierarchy as $slug => $name) {
             if (isset($rankDistribution[$name])) {
                 $sortedRankDistribution[$name] = $rankDistribution[$name];
                 unset($rankDistribution[$name]); // Entferne den Rang aus der Unsortierten
             }
        }
        // Füge alle übrigen (nicht in der Hierarchie definierten) Ränge hinzu.
        $sortedRankDistribution = array_merge($sortedRankDistribution, $rankDistribution);
        
        // 4. Daten an die View übergeben
        return view('dashboard', [
            'announcements' => $announcements,
            'reportCount' => $reportCount,
            'rankDistribution' => $sortedRankDistribution, // Die sortierte Verteilung
            'totalUsers' => $totalUsers,
        ]);
    }
}