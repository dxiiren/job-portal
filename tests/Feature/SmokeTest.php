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

    public function test_pages_carry_the_real_product_name_as_their_title(): void
    {
        // The layout shell used to ship the stock <title>Laravel</title>.
        $this->get(route('jobs.index'))
            ->assertOk()
            ->assertSee('<title>Job Portal</title>', false)
            ->assertDontSee('<title>Laravel</title>', false);
    }

    public function test_the_signed_in_nav_uses_a_plain_my_applications_label(): void
    {
        // The label used to interpolate the user's name and fall back to a
        // misspelled "Anynomus" for a nameless account.
        $this->actingAs(User::factory()->create())
            ->get(route('jobs.index'))
            ->assertOk()
            ->assertSee('My Applications')
            ->assertDontSee('Anynomus');
    }

    public function test_a_guest_is_invited_to_sign_in_instead_of_told_they_already_applied(): void
    {
        $job = Job::factory()
            ->for(Employer::factory()->for(User::factory()))
            ->create();

        $this->get(route('jobs.show', $job))
            ->assertOk()
            ->assertDontSee('You already applied to this job')
            ->assertSee('Sign in to apply');
    }
}
