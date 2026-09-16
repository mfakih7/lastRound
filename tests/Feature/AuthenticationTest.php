<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_displayed(): void
    {
        $this->get(route('login'))->assertOk();
    }

    public function test_login_works_for_admin(): void
    {
        $admin = User::factory()->admin()->create([
            'username' => 'admin',
            'password' => 'password',
        ]);

        $response = $this->post(route('login.store'), [
            'username' => 'admin',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_login_works_for_coach(): void
    {
        $coach = User::factory()->coach()->create([
            'username' => 'marcus',
            'password' => 'password',
        ]);

        $response = $this->post(route('login.store'), [
            'username' => 'marcus',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($coach);
        $response->assertRedirect(route('coach.dashboard'));
    }

    public function test_invalid_login_fails(): void
    {
        User::factory()->admin()->create([
            'username' => 'admin',
            'password' => 'password',
        ]);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'username' => 'admin',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('username');
    }

    public function test_guest_cannot_access_admin_dashboard(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_admin_can_access_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_coach_cannot_access_admin_routes(): void
    {
        $coach = User::factory()->coach()->create();

        $this->actingAs($coach)
            ->get(route('admin.dashboard'))
            ->assertForbidden();

        $this->actingAs($coach)
            ->get(route('admin.clients.index'))
            ->assertForbidden();

        $this->actingAs($coach)
            ->get(route('admin.packages.index'))
            ->assertForbidden();

        $this->actingAs($coach)
            ->get(route('admin.coaches.index'))
            ->assertForbidden();

        $this->actingAs($coach)
            ->get(route('admin.settings.index'))
            ->assertForbidden();
    }

    public function test_coach_can_access_coach_area(): void
    {
        $coach = User::factory()->coach()->create();

        $this->actingAs($coach)
            ->get(route('coach.dashboard'))
            ->assertOk();

        $this->actingAs($coach)
            ->get(route('coach.schedule.index'))
            ->assertOk();

        $this->actingAs($coach)
            ->get(route('coach.profile.show'))
            ->assertOk();
    }

    public function test_logout_works(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }

    public function test_registration_is_not_available(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register')->assertNotFound();
    }

    public function test_home_redirects_guest_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->admin()->inactive()->create([
            'username' => 'inactive-admin',
            'password' => 'password',
        ]);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'username' => 'inactive-admin',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('username');
    }

    public function test_inactive_coach_cannot_login(): void
    {
        User::factory()->coach()->inactive()->create([
            'username' => 'ahmad',
            'password' => 'password',
        ]);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'username' => 'ahmad',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('username');
    }

    public function test_deactivated_authenticated_coach_is_logged_out(): void
    {
        $coach = User::factory()->coach()->create();

        $coach->update(['is_active' => false]);

        $this->actingAs($coach)
            ->get(route('coach.schedule.index'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
