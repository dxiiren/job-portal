<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_seeder_creates_deterministic_demo_accounts(): void
    {
        $this->seed();

        // Job-seeker demo: always a plain applicant, never randomly an employer,
        // with no pre-seeded applications so every job can be applied to fresh.
        $seeker = User::query()->where('email', 'akmal@gmail.com')->sole();
        $this->assertNull($seeker->employer, 'demo job seeker must never own an employer profile');
        $this->assertSame(0, $seeker->jobApplications()->count());

        // Employer demo: owns a company with jobs to manage under /my-jobs.
        $employer = User::query()->where('email', 'employer@gmail.com')->sole();
        $this->assertNotNull($employer->employer, 'demo employer must own an employer profile');
        $this->assertGreaterThanOrEqual(3, $employer->employer->jobs()->count());
    }
}
