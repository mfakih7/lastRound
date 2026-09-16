<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_and_update_application_settings(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Application settings')
            ->assertSee('Admin account');

        $this->actingAs($admin)
            ->from(route('admin.settings.index'))
            ->put(route('admin.settings.update'), [
                'app_name' => 'Fight Lab',
                'phone' => '+961 70 000 000',
                'email' => 'gym@example.test',
                'address' => 'Beirut',
                'currency' => 'USD',
                'default_session_duration' => 45,
                'low_session_warning_threshold' => 3,
            ])
            ->assertRedirect(route('admin.settings.index'))
            ->assertSessionHas('success', 'Settings updated successfully.');

        $settings = app(SettingsService::class);
        $settings->forget();

        $this->assertSame('Fight Lab', $settings->appName());
        $this->assertSame(45, $settings->get('default_session_duration'));
        $this->assertSame(3, $settings->get('low_session_threshold'));
    }

    public function test_updated_application_name_appears_on_login(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            'app_name' => 'Fight Lab',
            'currency' => 'USD',
            'default_session_duration' => 60,
            'low_session_warning_threshold' => 2,
        ]);

        $this->post(route('logout'));

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Fight Lab');
    }

    public function test_admin_can_upload_and_replace_logo(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $file = UploadedFile::fake()->image('logo.png', 80, 80);

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), [
                'app_name' => 'LastRound',
                'currency' => 'USD',
                'default_session_duration' => 60,
                'low_session_warning_threshold' => 2,
                'logo' => $file,
            ])
            ->assertRedirect();

        $path = app(SettingsService::class)->logoPath();
        $this->assertNotNull($path);
        $this->assertStringStartsWith('branding/', $path);
        $this->assertStringNotContainsString('logo.png', (string) $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_admin_can_update_account_without_changing_role(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Head Coach',
            'username' => 'admin',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.settings.account'), [
                'name' => 'Mohamad',
                'username' => 'headcoach',
                'email' => 'mohamad@lastround.test',
                'role' => 'coach',
            ])
            ->assertRedirect();

        $admin->refresh();
        $this->assertSame('Mohamad', $admin->name);
        $this->assertSame('headcoach', $admin->username);
        $this->assertTrue($admin->isAdmin());
    }

    public function test_admin_password_change_requires_current_password(): void
    {
        $admin = User::factory()->admin()->create(['password' => 'password']);

        $this->actingAs($admin)
            ->from(route('admin.settings.index'))
            ->put(route('admin.settings.password'), [
                'current_password' => 'wrong-password',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertRedirect(route('admin.settings.index'))
            ->assertSessionHasErrors('current_password');

        $this->actingAs($admin)
            ->put(route('admin.settings.password'), [
                'current_password' => 'password',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertSessionHas('success', 'Password changed successfully.');

        $this->assertTrue(Hash::check('new-password-123', $admin->fresh()->password));
    }

    public function test_coach_cannot_access_settings(): void
    {
        $coach = User::factory()->coach()->create();

        $this->actingAs($coach)->get(route('admin.settings.index'))->assertForbidden();
        $this->actingAs($coach)->put(route('admin.settings.update'), [
            'app_name' => 'Hacked',
            'currency' => 'USD',
            'default_session_duration' => 60,
            'low_session_warning_threshold' => 2,
        ])->assertForbidden();
        $this->actingAs($coach)->put(route('admin.settings.account'), [
            'name' => 'Hacked',
            'username' => 'hacked',
        ])->assertForbidden();
        $this->actingAs($coach)->put(route('admin.settings.password'), [
            'current_password' => 'password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertForbidden();
    }
}
