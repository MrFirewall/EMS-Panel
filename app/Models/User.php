<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Lab404\Impersonate\Models\Impersonate;
use Illuminate\Database\Eloquent\Casts\Attribute; // Wichtig für den Accessor
use Illuminate\Support\Carbon; // Wichtig für Datumsberechnungen
use NotificationChannels\WebPush\HasPushSubscriptions;
use App\Models\Rank;
class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, Impersonate, HasPushSubscriptions;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'cfx_name',
        'cfx_id',
        'avatar',
        'status',        
        'rank',
        'personal_number',
        'employee_id',
        'email',
        'birthday',
        'discord_name',
        'forum_name',
        'special_functions',
        'second_faction',
        'hire_date',
        'last_edited_at',
        'last_edited_by',
    ];

    /**
     * Definiert die Hierarchie der Ränge.
     * @var array
     */
    private $rankHierarchy = [
        'chief'         => 11,
        'deputy chief'  => 10,
        'doctor'        => 9,
        'captain'       => 8,
        'lieutenant'    => 7,
        'supervisor'    => 6,
        's-emt'         => 5,
        'paramedic'     => 4,
        'a-emt'         => 3,
        'emt'           => 2,
        'trainee'       => 1,
    ];
    protected $casts = [
        'hire_date' => 'datetime',
    ];
    /**
     * NEU: Accessor, der "System" zurückgibt, wenn kein Bearbeiter gesetzt ist.
     */
    protected function lastEditor(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->last_edited_by ?? 'System',
        );
    }

    /**
     * NEU: Berechnet die Dienststunden basierend auf den ActivityLogs.
     *
     * @return array ['active_total_seconds' => int, 'archive_by_rank' => array]
     */
    public function calculateDutyHours(): array
    {
        // 1. Hole alle relevanten Logs für den User, chronologisch sortiert
        $logs = $this->activityLogs()
                     ->whereIn('log_type', ['DUTY_START', 'DUTY_END', 'UPDATED'])
                     ->orderBy('created_at', 'asc')
                     ->get();

        $archiveByRank = [];
        $activeTotalSeconds = 0;
        $lastStartLog = null;
        
        // Finde den initialen Rang des Benutzers zum Zeitpunkt der Einstellung
        $currentRank = $this->rank; 

        // 2. Durchlaufe alle Logs, um Stunden und Statusänderungen zu verarbeiten
        foreach ($logs as $log) {
            
            // Logik für Status- und Rangänderungen
            if ($log->log_type === 'UPDATED' && !empty($log->details)) {
                // A) Prüfen, ob der User inaktiv gesetzt wurde. Wenn ja, aktive Stunden zurücksetzen.
                if (str_contains($log->details, 'Status geändert:') && str_contains($log->details, '-> inaktiv')) {
                    $activeTotalSeconds = 0; // Stunden für die "aktive Zeit" werden zurückgesetzt
                }

                // B) Rangänderung aus dem Log-Text extrahieren
                if (preg_match('/Rang geändert:.*?-> ([\w-]+)/', $log->details, $matches)) {
                    $currentRank = $matches[1];
                }
            }

            // Logik zur Stundenberechnung
            if ($log->log_type === 'DUTY_START') {
                $lastStartLog = $log;
            } 
            elseif ($log->log_type === 'DUTY_END' && $lastStartLog) {
                $duration = $lastStartLog->created_at->diffInSeconds($log->created_at);
                
                // Zum Archiv für den aktuellen Rang hinzufügen
                if (!isset($archiveByRank[$currentRank])) {
                    $archiveByRank[$currentRank] = 0;
                }
                $archiveByRank[$currentRank] += $duration;

                // Zu den aktiven Stunden hinzufügen
                $activeTotalSeconds += $duration;
                
                $lastStartLog = null;
            }
        }

        // 3. Sonderfall: User ist aktuell noch im Dienst
        if ($lastStartLog) {
            $duration = $lastStartLog->created_at->diffInSeconds(Carbon::now());
            
            if (!isset($archiveByRank[$currentRank])) {
                $archiveByRank[$currentRank] = 0;
            }
            $archiveByRank[$currentRank] += $duration;
            $activeTotalSeconds += $duration;
        }

        return [
            'active_total_seconds' => $activeTotalSeconds,
            'archive_by_rank' => $archiveByRank,
        ];
    }

    public function calculateWeeklyHoursSinceEntry(): array
    {
        // 1. Prüfen, ob ein Einstellungsdatum vorhanden ist. Wenn nicht, leeres Array zurückgeben.
        if (!$this->hire_date) {
            return [];
        }

        // 2. Das Startdatum ist jetzt einfach das `hire_date`.
        $startDate = $this->hire_date->copy()->startOfDay();

        // 3. Hole alle relevanten Logs seit diesem Datum
        $logTypes = ['DUTY_START', 'DUTY_END', 'LEITSTELLE_START', 'LEITSTELLE_END'];
        $logs = $this->activityLogs()
            ->whereIn('log_type', $logTypes)
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at', 'asc')
            ->get();

        // 4. Verarbeite die Logs (Logik bleibt gleich)
        $weeklyData = [];
        $lastDutyStart = null;
        $lastLeitstelleStart = null;

        foreach ($logs as $log) {
            $kw = "KW" . $log->created_at->format('W');
            if (!isset($weeklyData[$kw])) {
                $weeklyData[$kw] = ['normal_seconds' => 0, 'leitstelle_seconds' => 0];
            }
            switch ($log->log_type) {
                case 'DUTY_START': $lastDutyStart = $log; break;
                case 'DUTY_END':
                    if ($lastDutyStart) {
                        $weeklyData[$kw]['normal_seconds'] += $lastDutyStart->created_at->diffInSeconds($log->created_at);
                        $lastDutyStart = null;
                    }
                    break;
                case 'LEITSTELLE_START': $lastLeitstelleStart = $log; break;
                case 'LEITSTELLE_END':
                    if ($lastLeitstelleStart) {
                        $weeklyData[$kw]['leitstelle_seconds'] += $lastLeitstelleStart->created_at->diffInSeconds($log->created_at);
                        $lastLeitstelleStart = null;
                    }
                    break;
            }
        }
        
        krsort($weeklyData);
        return $weeklyData;
    }

    /**
     * Ermittelt den Namen der höchsten Rolle, die der Benutzer hat.
     */
    public function getHighestRank(): string
    {
        $highestRankName = 'praktikant';
        $highestLevel = 0;

        foreach ($this->getRoleNames() as $roleName) {
            if (isset($this->rankHierarchy[$roleName]) && $this->rankHierarchy[$roleName] > $highestLevel) {
                $highestLevel = $this->rankHierarchy[$roleName];
                $highestRankName = $roleName;
            }
        }
        return $highestRankName;
    }

    /**
     * Gibt die "Stufe" des höchsten Ranges zurück.
     */
    public function getHighestRankLevel(): int
    {
        $highestLevel = 0;
        foreach ($this->getRoleNames() as $roleName) {
            if (isset($this->rankHierarchy[$roleName]) && $this->rankHierarchy[$roleName] > $highestLevel) {
                $highestLevel = $this->rankHierarchy[$roleName];
            }
        }
        return $highestLevel;
    }

    // --- Relationen ---
    public function activityLogs() { return $this->hasMany(ActivityLog::class); }
    public function receivedEvaluations() { return $this->hasMany(Evaluation::class, 'user_id'); }
    public function serviceRecords() { return $this->hasMany(ServiceRecord::class); }
    public function reports() { return $this->hasMany(Report::class); }
    public function vacations() { return $this->hasMany(Vacation::class); }
    public function attendedReports() { return $this->belongsToMany(Report::class, 'report_user'); }
    public function canImpersonate(): bool{ return $this->hasAnyRole('chief', 'Super-Admin'); }
    public function canBeImpersonated(): bool{ return !$this->hasAnyRole('chief', 'Super-Admin'); }
    public function prescriptions(){ return $this->hasMany(Prescription::class); }
    public function trainingModules(){ 
        return $this->belongsToMany(TrainingModule::class, 'training_module_user')
        ->using(\App\Models\Pivots\TrainingModuleUser::class)
        ->withPivot('assigned_by_user_id', 'completed_at', 'notes')
        ->withTimestamps();
    }

    public function qualifications(){ return $this->trainingModules()->wherePivot('status', 'bestanden'); }
    public function examAttempts(){ return $this->hasMany(ExamAttempt::class); }
    public function pushSubscriptions() { return $this->hasMany(\App\Models\PushSubscription::class); }
    public function rank(){ return $this->belongsTo(Rank::class); }
}

