<?php

namespace Tests\Feature;

use App\Models\Employer;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_root_url_redirects_to_the_job_listing(): void
    {
        $this->get('/')->assertRedirect(route('jobs.index'));
    }

    public function test_the_job_listing_page_renders_with_jobs(): void
    {
        $job = Job::factory()
            ->for(Employer::factory()->for(User::factory()))
            ->create();

        $this->get(route('jobs.index'))
            ->assertOk()
            ->assertSee($job->title);
    }
}
