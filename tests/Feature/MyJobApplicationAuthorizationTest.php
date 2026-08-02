<?php

namespace Tests\Feature;

use App\Models\Employer;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyJobApplicationAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function applicationFor(User $applicant): JobApplication
    {
        $job = Job::factory()
            ->for(Employer::factory()->for(User::factory()))
            ->create();

        return JobApplication::factory()
            ->for($applicant)
            ->for($job)
            ->create();
    }

    public function test_a_user_cannot_delete_another_users_job_application(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $application = $this->applicationFor($owner);

        $this->actingAs($attacker)
            ->delete(route('my-job-applications.destroy', $application))
            ->assertForbidden();

        $this->assertDatabaseHas('job_applications', [
            'id' => $application->id,
            'user_id' => $owner->id,
        ]);
        $this->assertSame(1, JobApplication::query()->count());
    }

    public function test_a_user_can_delete_their_own_job_application(): void
    {
        $owner = User::factory()->create();
        $application = $this->applicationFor($owner);

        $this->actingAs($owner)
            ->delete(route('my-job-applications.destroy', $application))
            ->assertRedirect()
            ->assertSessionHas('success', 'Job application removed.');

        $this->assertDatabaseMissing('job_applications', ['id' => $application->id]);
    }

    public function test_a_guest_cannot_delete_a_job_application(): void
    {
        $owner = User::factory()->create();
        $application = $this->applicationFor($owner);

        $this->delete(route('my-job-applications.destroy', $application))
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('job_applications', ['id' => $application->id]);
    }

    public function test_the_employer_who_owns_the_job_still_cannot_delete_an_applicants_application(): void
    {
        $applicant = User::factory()->create();
        $employerUser = User::factory()->create();
        $job = Job::factory()
            ->for(Employer::factory()->for($employerUser))
            ->create();
        $application = JobApplication::factory()
            ->for($applicant)
            ->for($job)
            ->create();

        $this->actingAs($employerUser)
            ->delete(route('my-job-applications.destroy', $application))
            ->assertForbidden();

        $this->assertDatabaseHas('job_applications', ['id' => $application->id]);
    }
}
