<?php

namespace App\Providers;

use App\Models\User;

use App\Models\Report;
use App\Policies\ReportPolicy;

use App\Models\Citizen;
use App\Policies\CitizenPolicy;

use App\Models\Prescription;
use App\Policies\PrescriptionPolicy;

use Spatie\Permission\Models\Permission;
use App\Policies\PermissionPolicy;

use Spatie\Permission\Models\TrainingModule;
use App\Policies\TrainingModulePolicy;

use App\Models\Evaluation;
use App\Policies\EvaluationPolicy;

use App\Models\ExamAttempt;
use App\Policies\ExamAttemptPolicy;

use App\Models\Exam;
use App\Policies\ExamPolicy;

use App\Models\NotificationRule;
use App\Policies\NotificationRulePolicy;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
 
class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Report::class => ReportPolicy::class,
        Citizen::class => CitizenPolicy::class,
        Prescription::class => PrescriptionPolicy::class,
        Permission::class => PermissionPolicy::class,
        TrainingModule::class => TrainingModulePolicy::class,
        Evaluation::class => EvaluationPolicy::class,
        ExamAttempt::class => ExamAttemptPolicy::class,
        Exam::class => ExamPolicy::class,
        NotificationRule::class => NotificationRulePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        Gate::before(function (User $user, string $ability) {
            if ($user->hasRole('Super-Admin', 'ems-director')) {
                return true;
            }
        });
    }
}