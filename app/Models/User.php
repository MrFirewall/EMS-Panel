<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Lab404\Impersonate\Models\Impersonate;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, Impersonate;

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
        'rank' // WICHTIG: 'rank' sollte hier sein, wenn es in store/update verwendet wird
    ];

    /**
     * Definiert die Hierarchie der Ränge.
     * @var array
     */
    private array $rankHierarchy = [
        'ems-director' => 8,
        'assistant-ems-director' => 7,
        'instructor' => 6,
        'emergency-doctor' => 5,
        'paramedic' => 4,
        'emt' => 3,
        'emt-trainee' => 2,
        'praktikant' => 1,
    ];

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

    public function receivedEvaluations() { return $this->hasMany(Evaluation::class, 'user_id'); }
    public function serviceRecords() { return $this->hasMany(ServiceRecord::class); }
    public function reports() { return $this->hasMany(Report::class); }
    public function examinations() { return $this->hasMany(Examination::class); }
    public function trainingModules() { return $this->hasMany(TrainingModule::class); }
    public function vacations() { return $this->hasMany(Vacation::class); }
    
    /**
     * Determines if this user can impersonate others.
     */
    public function canImpersonate(): bool
    {
        // ANGEPASST: Director und Super-Admin dürfen imitieren
        return $this->hasAnyRole('ems-director', 'Super-Admin');
    }

    /**
     * Determines if this user can be impersonated.
     */
    public function canBeImpersonated(): bool
    {
        // ANGEPASST: Director und Super-Admin können nicht imitiert werden
        return !$this->hasAnyRole('ems-director', 'Super-Admin');
    }
    public function attendedReports()
    {
        return $this->belongsToMany(Report::class, 'report_user');
    }
}
