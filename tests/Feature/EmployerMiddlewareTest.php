<?php

namespace Tests\Feature;

use App\Models\Employer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployerMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_is_sent_to_the_login_page(): void
    {
        // The auth middleware runs before EmployerMiddleware, so a guest never
        // reaches the employer check.
        $this->get(route('my-jobs.index'))->assertRedirect(route('login'));
    }

    public function test_a_signed_in_non_employer_is_redirected_to_the_employer_form(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('my-jobs.index'))
            ->assertRedirect(route('employer.create'))
            ->assertSessionHas('error', 'You need to register as an employer first!');
    }

    public function test_a_non_employer_cannot_reach_the_job_creation_form_either(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('my-jobs.create'))
            ->assertRedirect(route('employer.create'))
            ->assertSessionHas('error', 'You need to register as an employer first!');
    }

    public function test_an_employer_passes_through_to_their_job_list(): void
    {
        $employerUser = User::factory()->has(Employer::factory())->create();

        $this->actingAs($employerUser)
            ->get(route('my-jobs.index'))
            ->assertOk()
            ->assertSee('My Jobs');
    }
}
