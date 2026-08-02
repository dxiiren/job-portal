<?php

namespace Tests\Feature;

use App\Models\Employer;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\User;
use App\Policies\JobPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_soft_deleted_job_disappears_from_the_public_listing(): void
    {
        $live = Job::factory()
            ->for(Employer::factory()->for(User::factory()))
            ->create(['title' => 'Still Hiring Engineer']);
        $deleted = Job::factory()
            ->for(Employer::factory()->for(User::factory()))
            ->create(['title' => 'Withdrawn Engineer']);

        $deleted->delete();

        $this->get(route('jobs.index'))
            ->assertOk()
            ->assertSee($live->title)
            ->assertDontSee($deleted->title);
    }

    public function test_a_soft_deleted_job_404s_on_the_public_detail_page(): void
    {
        $job = Job::factory()
            ->for(Employer::factory()->for(User::factory()))
            ->create();

        $this->get(route('jobs.show', $job))->assertOk();

        $job->delete();

        $this->get(route('jobs.show', $job))->assertNotFound();
    }

    public function test_a_soft_deleted_job_is_still_listed_for_its_own_employer(): void
    {
        $employerUser = User::factory()->create();
        $job = Job::factory()
            ->for(Employer::factory()->for($employerUser))
            ->create(['title' => 'Withdrawn Engineer']);

        $job->delete();

        $this->actingAs($employerUser)
            ->get(route('my-jobs.index'))
            ->assertOk()
            ->assertSee('Withdrawn Engineer')
            ->assertSee('Deleted');
    }

    public function test_a_soft_deleted_job_is_still_shown_in_the_applicants_list(): void
    {
        $applicant = User::factory()->create();
        $job = Job::factory()
            ->for(Employer::factory()->for(User::factory()))
            ->create(['title' => 'Withdrawn Engineer']);
        JobApplication::factory()->for($applicant)->for($job)->create();

        $job->delete();

        $this->actingAs($applicant)
            ->get(route('my-job-applications.index'))
            ->assertOk()
            ->assertSee('Withdrawn Engineer');
    }

    public function test_only_the_owning_employer_may_restore_a_soft_deleted_job(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $job = Job::factory()
            ->for(Employer::factory()->for($owner))
            ->create();
        $job->delete();

        $policy = new JobPolicy;

        $this->assertTrue($policy->restore($owner, $job));
        $this->assertFalse($policy->restore($stranger, $job));
    }

    public function test_only_the_owning_employer_may_force_delete_a_job(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $job = Job::factory()
            ->for(Employer::factory()->for($owner))
            ->create();
        $job->delete();

        $policy = new JobPolicy;

        $this->assertTrue($policy->forceDelete($owner, $job));
        $this->assertFalse($policy->forceDelete($stranger, $job));
    }

    public function test_a_restored_job_returns_to_the_public_listing(): void
    {
        $job = Job::factory()
            ->for(Employer::factory()->for(User::factory()))
            ->create(['title' => 'Back On The Board']);

        $job->delete();
        $this->get(route('jobs.index'))->assertDontSee($job->title);

        $job->restore();

        $this->assertNull($job->fresh()->deleted_at);
        $this->get(route('jobs.index'))->assertSee($job->title);
    }
}
