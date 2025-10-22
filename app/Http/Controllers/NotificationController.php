<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ExamAttempt; // Beispiel für eine Benachrichtigungsquelle
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function fetch()
    {
        if (!Auth::check() || !Auth::user()->can('evaluations.view.all')) {
            return response()->json(['count' => 0, 'items' => []]);
        }
        
        // Beispiel: Suche nach kürzlich geflaggten Prüfungen
        $flaggedExams = ExamAttempt::where('status', 'submitted')
            ->whereNotNull('flags')
            ->where('updated_at', '>', now()->subDay()) // nur von heute
            ->with('user')
            ->latest('updated_at')
            ->limit(5)
            ->get();

        $notifications = [];
        foreach($flaggedExams as $exam) {
            $notifications[] = [
                'icon' => 'fas fa-exclamation-triangle text-warning',
                'text' => "Prüfung von {$exam->user->name} geflaggt",
                'url' => '#', // URL zur Detailansicht der Auswertung
                'time' => $exam->updated_at->diffForHumans()
            ];
        }

        return response()->json([
            'count' => count($notifications),
            'items_html' => view('layouts._notifications', ['notifications' => $notifications])->render()
        ]);
    }
}
