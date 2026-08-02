<?php

namespace Tests\Feature;

use App\Models\Employer;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class JobRequestValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $employerUser;

    private Job $job;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employerUser = User::factory()->create();
        $this->job = Job::factory()
            ->for(Employer::factory()->for($this->employerUser))
            ->create(['title' => 'Original title']);
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Backend Engineer',
            'location' => 'Kuala Lumpur',
            'salary' => 9000,
            'description' => 'Build and maintain the API.',
            'experience' => 'entry',
            'category' => 'IT',
        ], $overrides);
    }

    private function store(array $overrides = []): TestResponse
    {
        return $this->actingAs($this->employerUser)
            ->from(route('my-jobs.create'))
            ->post(route('my-jobs.store'), $this->payload($overrides));
    }

    private function update(array $overrides = []): TestResponse
    {
        return $this->actingAs($this->employerUser)
            ->from(route('my-jobs.edit', $this->job))
            ->patch(route('my-jobs.update', $this->job), $this->payload($overrides));
    }

    public function test_a_salary_below_the_5000_floor_is_rejected_on_store(): void
    {
        $this->store(['salary' => 4999])->assertSessionHasErrors('salary');

        $this->assertSame(1, Job::query()->count());
    }

    public function test_a_salary_below_the_5000_floor_is_rejected_on_update(): void
    {
        $this->update(['salary' => 4999])->assertSessionHasErrors('salary');

        $this->assertSame('Original title', $this->job->fresh()->title);
    }

    public function test_the_5000_salary_floor_itself_is_accepted(): void
    {
        $this->store(['salary' => 5000])->assertRedirect(route('my-jobs.index'));

        $this->assertDatabaseHas('offered_jobs', ['title' => 'Backend Engineer', 'salary' => 5000]);
    }

    public function test_a_non_numeric_salary_is_rejected(): void
    {
        $this->store(['salary' => 'a lot'])->assertSessionHasErrors('salary');

        $this->assertSame(1, Job::query()->count());
    }

    public function test_an_experience_value_outside_the_enum_is_rejected_on_store(): void
    {
        $this->store(['experience' => 'wizard'])->assertSessionHasErrors('experience');

        $this->assertSame(1, Job::query()->count());
    }

    public function test_an_experience_value_outside_the_enum_is_rejected_on_update(): void
    {
        $this->update(['experience' => 'wizard'])->assertSessionHasErrors('experience');

        $this->assertSame('Original title', $this->job->fresh()->title);
    }

    public function test_a_category_outside_the_enum_is_rejected_on_store(): void
    {
        $this->store(['category' => 'Underwater Basket Weaving'])->assertSessionHasErrors('category');

        $this->assertSame(1, Job::query()->count());
    }

    public function test_a_category_outside_the_enum_is_rejected_on_update(): void
    {
        $this->update(['category' => 'Underwater Basket Weaving'])->assertSessionHasErrors('category');

        $this->assertSame('Original title', $this->job->fresh()->title);
    }

    public function test_a_title_longer_than_255_characters_is_rejected_on_store(): void
    {
        $this->store(['title' => str_repeat('a', 256)])->assertSessionHasErrors('title');

        $this->assertSame(1, Job::query()->count());
    }

    public function test_a_title_longer_than_255_characters_is_rejected_on_update(): void
    {
        $this->update(['title' => str_repeat('a', 256)])->assertSessionHasErrors('title');

        $this->assertSame('Original title', $this->job->fresh()->title);
    }

    public function test_a_title_of_exactly_255_characters_is_accepted(): void
    {
        $title = str_repeat('a', 255);

        $this->store(['title' => $title])->assertRedirect(route('my-jobs.index'));

        $this->assertDatabaseHas('offered_jobs', ['title' => $title]);
    }

    public function test_every_required_field_is_enforced(): void
    {
        $this->actingAs($this->employerUser)
            ->from(route('my-jobs.create'))
            ->post(route('my-jobs.store'), [])
            ->assertSessionHasErrors(['title', 'location', 'salary', 'description', 'experience', 'category']);

        $this->assertSame(1, Job::query()->count());
    }

    public function test_a_valid_payload_creates_the_job_for_the_signed_in_employer(): void
    {
        $this->store()->assertRedirect(route('my-jobs.index'));

        $this->assertDatabaseHas('offered_jobs', [
            'title' => 'Backend Engineer',
            'employer_id' => $this->employerUser->employer->id,
        ]);
    }
}
