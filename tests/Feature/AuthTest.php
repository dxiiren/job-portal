<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_login_form_renders(): void
    {
        $this->get(route('auth.create'))
            ->assertOk()
            ->assertSee('Job Portal', false);
    }

    public function test_the_login_alias_route_points_at_the_login_form(): void
    {
        $this->get('/login')->assertRedirect(route('auth.create'));
    }

    public function test_valid_credentials_sign_the_user_in_and_redirect_to_the_intended_page(): void
    {
        $user = User::factory()->create(['email' => 'seeker@example.test']);

        $this->post(route('auth.store'), [
            'email' => 'seeker@example.test',
            'password' => 'password',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_user_is_returned_to_the_page_they_were_intercepted_on(): void
    {
        $user = User::factory()->create(['email' => 'seeker@example.test']);

        // Hitting a guarded page as a guest stores the intended url in the session.
        $this->get(route('my-job-applications.index'))->assertRedirect(route('login'));

        $this->post(route('auth.store'), [
            'email' => 'seeker@example.test',
            'password' => 'password',
        ])->assertRedirect(route('my-job-applications.index'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_bounce_back_with_an_error_flash(): void
    {
        User::factory()->create(['email' => 'seeker@example.test']);

        $this->from(route('auth.create'))
            ->post(route('auth.store'), [
                'email' => 'seeker@example.test',
                'password' => 'not-the-password',
            ])
            ->assertRedirect(route('auth.create'))
            ->assertSessionHas('error', 'Invalid credentials');

        $this->assertGuest();
    }

    public function test_a_malformed_email_fails_validation_before_any_login_attempt(): void
    {
        $this->from(route('auth.create'))
            ->post(route('auth.store'), [
                'email' => 'not-an-email',
                'password' => 'password',
            ])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_a_missing_password_fails_validation(): void
    {
        $this->from(route('auth.create'))
            ->post(route('auth.store'), ['email' => 'seeker@example.test'])
            ->assertSessionHasErrors('password');

        $this->assertGuest();
    }

    public function test_the_remember_flag_issues_a_remember_token_cookie(): void
    {
        $user = User::factory()->create(['email' => 'seeker@example.test']);

        $withoutRemember = $this->post(route('auth.store'), [
            'email' => 'seeker@example.test',
            'password' => 'password',
        ]);
        $this->assertNull($withoutRemember->getCookie(auth()->guard()->getRecallerName()));

        auth()->logout();
        $this->flushSession();

        $withRemember = $this->post(route('auth.store'), [
            'email' => 'seeker@example.test',
            'password' => 'password',
            'remember' => 'on',
        ]);

        $recaller = $withRemember->getCookie(auth()->guard()->getRecallerName());
        $this->assertNotNull($recaller, 'the remember flag must issue a recaller cookie');
        $this->assertNotNull($user->fresh()->remember_token);
    }

    public function test_logout_signs_the_user_out_and_invalidates_the_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        session(['scratch' => 'value']);
        $this->assertAuthenticatedAs($user);

        $this->delete(route('logout'))->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull(session('scratch'));
    }

    public function test_logout_is_not_reachable_with_a_get(): void
    {
        // Logout is a DELETE — a bare link must not be able to sign anyone out.
        $this->get('/logout')->assertMethodNotAllowed();
    }
}
