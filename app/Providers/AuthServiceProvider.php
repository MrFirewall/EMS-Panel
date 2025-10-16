<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Report;
use App\Models\Citizen;
use App\Policies\ReportPolicy;
use App\Policies\CitizenPolicy;
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