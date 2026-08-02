<?php

namespace App\Providers;

use App\Models\Employer;
use App\Models\Job;
use App\Models\JobApplication;
use App\Policies\EmployerPolicy;
use App\Policies\JobApplicationPolicy;
use App\Policies\JobPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Job::class, JobPolicy::class);
        Gate::policy(Employer::class, EmployerPolicy::class);
        Gate::policy(JobApplication::class, JobApplicationPolicy::class);
    }
}
