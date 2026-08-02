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

class JobApplicationCvTest extends TestCase
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

    public function test_a_non_pdf_cv_is_rejected_and_nothing_is_stored(): void
    {
        $this->actingAs($this->user)
            ->from(route('job.application.create', $this->job))
            ->post(route('job.application.store', $this->job), [
                'expected_salary' => 5000,
                'cv' => UploadedFile::fake()->create('cv.exe', 100),
            ])
            ->assertSessionHasErrors('cv');

        Storage::disk('local')->assertDirectoryEmpty('cvs');
        $this->assertSame(0, JobApplication::query()->count());
    }

    public function test_a_cv_over_the_2mb_cap_is_rejected(): void
    {
        $this->actingAs($this->user)
            ->post(route('job.application.store', $this->job), [
                'expected_salary' => 5000,
                'cv' => UploadedFile::fake()->create('cv.pdf', 3000, 'application/pdf'),
            ])
            ->assertSessionHasErrors('cv');

        Storage::disk('local')->assertDirectoryEmpty('cvs');
        $this->assertSame(0, JobApplication::query()->count());
    }

    public function test_a_valid_pdf_application_is_stored(): void
    {
        $this->actingAs($this->user)
            ->post(route('job.application.store', $this->job), [
                'expected_salary' => 5000,
                'cv' => UploadedFile::fake()->create('cv.pdf', 500, 'application/pdf'),
            ])
            ->assertRedirect(route('jobs.show', $this->job));

        $application = JobApplication::query()->sole();
        $this->assertSame($this->user->id, $application->user_id);
        Storage::disk('local')->assertExists($application->cv_path);
    }
}
