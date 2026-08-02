<?php

namespace Tests\Feature;

use App\Models\Employer;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class JobApplicationSalaryValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Job $job;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->user = User::factory()->create();
        $this->job = Job::factory()
            ->for(Employer::factory()->for(User::factory()))
            ->create();
    }

    private function apply(mixed $salary): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->user)
            ->from(route('job.application.create', $this->job))
            ->post(route('job.application.store', $this->job), [
                'expected_salary' => $salary,
                'cv' => UploadedFile::fake()->create('cv.pdf', 500, 'application/pdf'),
            ]);
    }

    public function test_a_non_numeric_salary_is_rejected(): void
    {
        $this->apply('abc')->assertSessionHasErrors('expected_salary');

        $this->assertSame(0, JobApplication::query()->count());
    }

    public function test_an_absurdly_large_salary_is_rejected(): void
    {
        $this->apply('999999999999')->assertSessionHasErrors('expected_salary');

        $this->assertSame(0, JobApplication::query()->count());
    }

    public function test_a_zero_salary_is_rejected(): void
    {
        $this->apply('0')->assertSessionHasErrors('expected_salary');

        $this->assertSame(0, JobApplication::query()->count());
    }

    public function test_an_in_range_salary_is_accepted(): void
    {
        $this->apply('45000')->assertRedirect(route('jobs.show', $this->job));

        $application = JobApplication::query()->sole();
        $this->assertSame(45000, (int) $application->expected_salary);
    }
}
