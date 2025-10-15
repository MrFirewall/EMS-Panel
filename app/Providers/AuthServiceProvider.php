<?php

namespace App\Providers;

use App\Models\Report;         // <-- Hinzufügen
use App\Policies\ReportPolicy; // <-- Hinzufügen
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
        Report::class => ReportPolicy::class, // <-- Diese Zeile ist entscheidend
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        Gate::before(function (User $user, string $ability) {
            if ($user->hasRole('Super-Admin')) {
                return true;
            }
        });
    }
}