<?php

namespace Tests\Feature;

use App\Models\Employer;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_employer_cannot_edit_a_job_that_has_applications(): void
    {
        $employerUser = User::factory()->create();
        $job = Job::factory()
            ->for(Employer::factory()->for($employerUser))
            ->create();
        JobApplication::factory()
            ->for(User::factory())
            ->for($job)
            ->create();

        $this->actingAs($employerUser)
            ->patch(route('my-jobs.update', $job), [
                'title' => 'Updated title',
                'location' => 'Kuala Lumpur',
                'salary' => 9000,
                'description' => 'Updated description.',
                'experience' => 'entry',
                'category' => 'IT',
            ])
            ->assertForbidden();

        $this->assertNotSame('Updated title', $job->fresh()->title);
    }

    public function test_employer_can_edit_their_job_before_any_application_arrives(): void
    {
        $employerUser = User::factory()->create();
        $job = Job::factory()
            ->for(Employer::factory()->for($employerUser))
            ->create();

        $this->actingAs($employerUser)
            ->patch(route('my-jobs.update', $job), [
                'title' => 'Updated title',
                'location' => 'Kuala Lumpur',
                'salary' => 9000,
                'description' => 'Updated description.',
                'experience' => 'entry',
                'category' => 'IT',
            ])
            ->assertRedirect(route('my-jobs.index'));

        $this->assertSame('Updated title', $job->fresh()->title);
    }

    public function test_employer_cannot_edit_another_employers_job(): void
    {
        $job = Job::factory()
            ->for(Employer::factory()->for(User::factory()))
            ->create();
        $otherEmployerUser = User::factory()->has(Employer::factory())->create();

        $this->actingAs($otherEmployerUser)
            ->patch(route('my-jobs.update', $job), ['title' => 'Hijacked'])
            ->assertForbidden();

        $this->assertNotSame('Hijacked', $job->fresh()->title);
    }

    public function test_user_cannot_apply_twice_to_the_same_job(): void
    {
        $user = User::factory()->create();
        $job = Job::factory()
            ->for(Employer::factory()->for(User::factory()))
            ->create();
        JobApplication::factory()
            ->for($user)
            ->for($job)
            ->create();

        $this->actingAs($user)
            ->post(route('job.application.store', $job), [
                'expected_salary' => 5000,
            ])
            ->assertForbidden();

        $this->assertSame(1, JobApplication::query()->count());
    }
}
