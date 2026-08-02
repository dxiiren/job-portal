<?php

namespace Tests\Feature;

use App\Models\Employer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployerPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_without_an_employer_can_open_the_registration_form(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('employer.create'))
            ->assertOk();
    }

    public function test_a_user_who_already_owns_an_employer_cannot_register_a_second_one(): void
    {
        $employerUser = User::factory()->has(Employer::factory())->create();

        $this->actingAs($employerUser)
            ->get(route('employer.create'))
            ->assertForbidden();

        $this->actingAs($employerUser)
            ->post(route('employer.store'), ['company_name' => 'Second Company Sdn Bhd'])
            ->assertForbidden();

        $this->assertSame(1, Employer::query()->count());
    }

    public function test_a_company_name_must_be_unique(): void
    {
        Employer::factory()->for(User::factory())->create(['company_name' => 'Taken Sdn Bhd']);

        $this->actingAs(User::factory()->create())
            ->from(route('employer.create'))
            ->post(route('employer.store'), ['company_name' => 'Taken Sdn Bhd'])
            ->assertSessionHasErrors('company_name');

        $this->assertSame(1, Employer::query()->count());
    }

    public function test_a_company_name_shorter_than_three_characters_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->from(route('employer.create'))
            ->post(route('employer.store'), ['company_name' => 'AB'])
            ->assertSessionHasErrors('company_name');

        $this->assertSame(0, Employer::query()->count());
    }

    public function test_a_company_name_is_required(): void
    {
        $this->actingAs(User::factory()->create())
            ->from(route('employer.create'))
            ->post(route('employer.store'), [])
            ->assertSessionHasErrors('company_name');

        $this->assertSame(0, Employer::query()->count());
    }

    public function test_a_valid_registration_creates_the_employer_for_the_signed_in_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('employer.store'), ['company_name' => 'Fresh Start Sdn Bhd'])
            ->assertRedirect(route('jobs.index'))
            ->assertSessionHas('success', 'Your employer account was created!');

        $this->assertDatabaseHas('employers', [
            'company_name' => 'Fresh Start Sdn Bhd',
            'user_id' => $user->id,
        ]);
    }
}
