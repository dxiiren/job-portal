<?php

namespace Tests\Feature;

use App\Models\Employer;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyJobDeleteAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_employer_cannot_delete_another_employers_job(): void
    {
        $job = Job::factory()
            ->for(Employer::factory()->for(User::factory()))
            ->create();
        $otherEmployerUser = User::factory()->has(Employer::factory())->create();

        $this->actingAs($otherEmployerUser)
            ->delete(route('my-jobs.destroy', $job))
            ->assertForbidden();

        $this->assertNotNull($job->fresh());
        $this->assertNull($job->fresh()->deleted_at);
    }

    public function test_an_employer_can_delete_their_own_job(): void
    {
        $employerUser = User::factory()->create();
        $job = Job::factory()
            ->for(Employer::factory()->for($employerUser))
            ->create();

        $this->actingAs($employerUser)
            ->delete(route('my-jobs.destroy', $job))
            ->assertRedirect(route('my-jobs.index'))
            ->assertSessionHas('success', 'Job deleted.');

        $this->assertSoftDeleted($job);
    }
}
