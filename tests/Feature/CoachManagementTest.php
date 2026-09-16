<?php

namespace Tests\Feature;

use App\Enums\ClientStatus;
use App\Enums\UserRole;
use App\Models\Client;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CoachManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_coaches(): void
    {
        $admin = User::factory()->admin()->withProfile()->create();
        User::factory()->coach()->withProfile()->create(['name' => 'Ahmad Hassan', 'username' => 'ahmad']);

        $this->actingAs($admin)
            ->get(route('admin.coaches.index'))
            ->assertOk()
            ->assertSee('Ahmad Hassan')
            ->assertSee('ahmad');
    }

    public function test_admin_can_create_coach_user_and_profile(): void
    {
        $admin = User::factory()->admin()->withProfile()->create();

        $response = $this->actingAs($admin)->post(route('admin.coaches.store'), [
            'name' => 'Ahmad Hassan',
            'username' => 'Ahmad',
            'email' => 'ahmad@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
            'phone' => '+961 70 000 001',
            'notes' => 'Pad work specialist.',
            'is_active' => '1',
            'is_available' => '1',
        ]);

        $coach = User::query()->where('username', 'ahmad')->first();

        $this->assertNotNull($coach);
        $this->assertSame('Ahmad Hassan', $coach->name);
        $this->assertSame(UserRole::Coach, $coach->role);
        $this->assertTrue($coach->is_active);
        $this->assertTrue(Hash::check('password', $coach->password));
        $this->assertNotSame('password', $coach->getRawOriginal('password'));
        $this->assertNotNull($coach->coachProfile);
        $this->assertSame('+961 70 000 001', $coach->coachProfile->phone);
        $this->assertTrue($coach->coachProfile->is_available);
        $response->assertRedirect(route('admin.coaches.show', $coach));
        $response->assertSessionHas('success', 'Coach created successfully.');
    }

    public function test_duplicate_username_is_rejected(): void
    {
        $admin = User::factory()->admin()->withProfile()->create();
        User::factory()->coach()->withProfile()->create(['username' => 'ahmad']);

        $this->actingAs($admin)
            ->from(route('admin.coaches.create'))
            ->post(route('admin.coaches.store'), [
                'name' => 'Ahmad Hassan',
                'username' => 'ahmad',
                'password' => 'password',
                'password_confirmation' => 'password',
                'is_active' => '1',
                'is_available' => '1',
            ])
            ->assertRedirect(route('admin.coaches.create'))
            ->assertSessionHasErrors('username');
    }

    public function test_invalid_coach_form_is_rejected(): void
    {
        $admin = User::factory()->admin()->withProfile()->create();

        $this->actingAs($admin)
            ->from(route('admin.coaches.create'))
            ->post(route('admin.coaches.store'), [
                'name' => '',
                'username' => 'bad username!',
                'password' => 'short',
                'password_confirmation' => 'mismatch',
            ])
            ->assertRedirect(route('admin.coaches.create'))
            ->assertSessionHasErrors(['name', 'username', 'password']);
    }

    public function test_admin_can_edit_coach_and_empty_password_preserves_existing(): void
    {
        $admin = User::factory()->admin()->withProfile()->create();
        $coach = User::factory()->coach()->withProfile()->create([
            'name' => 'Old Name',
            'username' => 'oldname',
            'password' => 'password',
        ]);
        $originalHash = $coach->password;

        $this->actingAs($admin)
            ->put(route('admin.coaches.update', $coach), [
                'name' => 'New Name',
                'username' => 'oldname',
                'password' => '',
                'password_confirmation' => '',
                'phone' => '+961 70 111 111',
                'is_active' => '1',
                'is_available' => '1',
            ])
            ->assertRedirect(route('admin.coaches.show', $coach));

        $coach->refresh();

        $this->assertSame('New Name', $coach->name);
        $this->assertSame($originalHash, $coach->password);
        $this->assertTrue(Hash::check('password', $coach->password));
        $this->assertSame('+961 70 111 111', $coach->coachProfile->phone);
    }

    public function test_new_password_on_edit_replaces_old_password(): void
    {
        $admin = User::factory()->admin()->withProfile()->create();
        $coach = User::factory()->coach()->withProfile()->create([
            'username' => 'ahmad',
            'password' => 'password',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.coaches.update', $coach), [
                'name' => $coach->name,
                'username' => 'ahmad',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
                'is_active' => '1',
                'is_available' => '1',
            ])
            ->assertRedirect(route('admin.coaches.show', $coach));

        $coach->refresh();

        $this->assertTrue(Hash::check('new-password-123', $coach->password));
        $this->assertFalse(Hash::check('password', $coach->password));
    }

    public function test_admin_can_change_coach_password_from_dedicated_action(): void
    {
        $admin = User::factory()->admin()->withProfile()->create();
        $coach = User::factory()->coach()->withProfile()->create(['password' => 'password']);

        $this->actingAs($admin)
            ->put(route('admin.coaches.password.update', $coach), [
                'password' => 'reset-password-123',
                'password_confirmation' => 'reset-password-123',
            ])
            ->assertRedirect(route('admin.coaches.show', $coach))
            ->assertSessionHas('success', 'Password updated successfully.');

        $this->assertTrue(Hash::check('reset-password-123', $coach->fresh()->password));
    }

    public function test_admin_can_deactivate_coach_and_they_cannot_log_in(): void
    {
        $admin = User::factory()->admin()->withProfile()->create();
        $coach = User::factory()->coach()->withProfile()->create([
            'username' => 'ahmad',
            'password' => 'password',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.coaches.deactivate', $coach))
            ->assertRedirect()
            ->assertSessionHas('success', 'Coach account deactivated successfully.');

        $this->assertFalse($coach->fresh()->is_active);

        $this->post(route('logout'));

        $this->from(route('login'))
            ->post(route('login.store'), [
                'username' => 'ahmad',
                'password' => 'password',
            ])
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_admin_can_reactivate_coach(): void
    {
        $admin = User::factory()->admin()->withProfile()->create();
        $coach = User::factory()->coach()->inactive()->withProfile()->create();

        $this->actingAs($admin)
            ->post(route('admin.coaches.activate', $coach))
            ->assertSessionHas('success', 'Coach account activated successfully.');

        $this->assertTrue($coach->fresh()->is_active);
    }

    public function test_coach_with_history_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->withProfile()->create();
        $coach = User::factory()->coach()->withProfile()->create();
        TrainingSession::factory()->create(['coach_user_id' => $coach->id]);

        $this->actingAs($admin)
            ->from(route('admin.coaches.show', $coach))
            ->delete(route('admin.coaches.destroy', $coach))
            ->assertRedirect(route('admin.coaches.show', $coach))
            ->assertSessionHas('error', 'This coach cannot be deleted because historical records exist.');

        $this->assertDatabaseHas('users', ['id' => $coach->id]);
        $this->assertDatabaseHas('training_sessions', ['coach_user_id' => $coach->id]);
    }

    public function test_coach_without_history_can_be_deleted(): void
    {
        $admin = User::factory()->admin()->withProfile()->create();
        $coach = User::factory()->coach()->withProfile()->create(['username' => 'temporary']);

        $this->actingAs($admin)
            ->delete(route('admin.coaches.destroy', $coach))
            ->assertRedirect(route('admin.coaches.index'))
            ->assertSessionHas('success', 'Coach deleted successfully.');

        $this->assertDatabaseMissing('users', ['id' => $coach->id]);
    }

    public function test_admin_account_cannot_be_deleted_or_deactivated_through_coaches(): void
    {
        $admin = User::factory()->admin()->withProfile()->create(['username' => 'admin']);

        $this->actingAs($admin)
            ->from(route('admin.coaches.show', $admin))
            ->delete(route('admin.coaches.destroy', $admin))
            ->assertSessionHas('error', 'The Head Coach / Admin account cannot be deleted.');

        $this->actingAs($admin)
            ->post(route('admin.coaches.deactivate', $admin))
            ->assertSessionHas('error', 'The Head Coach / Admin account cannot be deactivated.');

        $admin->refresh();

        $this->assertTrue($admin->is_active);
        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_can_enable_head_coach_profile_and_remain_admin(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Mohamad', 'username' => 'admin']);

        $this->assertFalse($admin->canActAsCoach());

        $this->actingAs($admin)
            ->post(route('admin.coaches.enable', $admin))
            ->assertRedirect(route('admin.coaches.show', $admin));

        $admin->refresh();

        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertTrue($admin->canActAsCoach());
        $this->assertTrue($admin->isAssignableCoach());
        $this->assertTrue(
            User::query()->assignableCoaches()->where('id', $admin->id)->exists()
        );
    }

    public function test_assignable_coaches_include_admin_and_exclude_inactive_or_unavailable(): void
    {
        $admin = User::factory()->admin()->withProfile()->create(['name' => 'Mohamad']);
        $activeCoach = User::factory()->coach()->withProfile()->create(['name' => 'Ahmad']);
        $inactiveCoach = User::factory()->coach()->inactive()->withProfile()->create(['name' => 'Karim']);
        $unavailableCoach = User::factory()->coach()->withProfile(['is_available' => false])->create(['name' => 'Ali']);
        User::factory()->coach()->create(['name' => 'No Profile']);

        $ids = User::query()->assignableCoaches()->pluck('id');

        $this->assertTrue($ids->contains($admin->id));
        $this->assertTrue($ids->contains($activeCoach->id));
        $this->assertFalse($ids->contains($inactiveCoach->id));
        $this->assertFalse($ids->contains($unavailableCoach->id));
        $this->assertSame('Mohamad — Head Coach', $admin->trainerLabel());
    }

    public function test_client_form_uses_assignable_coaches_and_keeps_historical_preferred_coach(): void
    {
        $admin = User::factory()->admin()->withProfile()->create(['name' => 'Mohamad']);
        $activeCoach = User::factory()->coach()->withProfile()->create(['name' => 'Ahmad']);
        $inactiveCoach = User::factory()->coach()->inactive()->withProfile()->create(['name' => 'Old Coach']);
        $client = Client::factory()->forCoach($inactiveCoach)->create();

        $this->actingAs($admin)
            ->get(route('admin.clients.create'))
            ->assertOk()
            ->assertSee('Mohamad — Head Coach')
            ->assertSee('Ahmad')
            ->assertDontSee('Old Coach');

        $this->actingAs($admin)
            ->get(route('admin.clients.edit', $client))
            ->assertOk()
            ->assertSee('Old Coach')
            ->assertSee('Mohamad — Head Coach');

        $this->actingAs($admin)
            ->from(route('admin.clients.create'))
            ->post(route('admin.clients.store'), [
                'full_name' => 'New Client',
                'phone' => '+961 70 000 002',
                'preferred_coach_id' => $inactiveCoach->id,
                'status' => ClientStatus::Active->value,
            ])
            ->assertRedirect(route('admin.clients.create'))
            ->assertSessionHasErrors('preferred_coach_id');
    }

    public function test_preferred_clients_are_shown_on_coach_profile(): void
    {
        $admin = User::factory()->admin()->withProfile()->create();
        $coach = User::factory()->coach()->withProfile()->create(['name' => 'Ahmad Hassan']);
        $client = Client::factory()->forCoach($coach)->create(['full_name' => 'John Smith']);

        $this->actingAs($admin)
            ->get(route('admin.coaches.show', $coach))
            ->assertOk()
            ->assertSee('Ahmad Hassan')
            ->assertSee('Preferred clients')
            ->assertSee('John Smith')
            ->assertSee($client->phone);
    }

    public function test_coach_listing_search_filters_sort_and_pagination_are_preserved(): void
    {
        $admin = User::factory()->admin()->withProfile()->create();
        User::factory()->coach()->withProfile()->create([
            'name' => 'Ahmad Hassan',
            'username' => 'ahmad',
        ]);
        User::factory()->coach()->withProfile()->create([
            'name' => 'Sofia Reed',
            'username' => 'sofia',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.coaches.index', [
                'search' => 'ahmad',
                'status' => 'active',
                'availability' => 'available',
                'type' => 'coach',
                'sort' => 'username',
                'direction' => 'asc',
                'per_page' => 10,
            ]))
            ->assertOk()
            ->assertSee('Ahmad Hassan')
            ->assertDontSee('Sofia Reed');
    }

    public function test_admin_can_view_coach_schedule_readonly(): void
    {
        $admin = User::factory()->admin()->withProfile()->create();
        $coach = User::factory()->coach()->withProfile()->create();
        $client = Client::factory()->create(['full_name' => 'John Smith']);
        TrainingSession::factory()->create([
            'coach_user_id' => $coach->id,
            'client_id' => $client->id,
            'session_date' => today()->toDateString(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.coaches.schedule', $coach))
            ->assertOk()
            ->assertSee('John Smith')
            ->assertSee('Read-only');
    }

    public function test_coach_cannot_create_or_edit_coaches(): void
    {
        $coach = User::factory()->coach()->withProfile()->create();
        $other = User::factory()->coach()->withProfile()->create();

        $this->actingAs($coach)
            ->get(route('admin.coaches.create'))
            ->assertForbidden();

        $this->actingAs($coach)
            ->post(route('admin.coaches.store'), [
                'name' => 'Intruder',
                'username' => 'intruder',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertForbidden();

        $this->actingAs($coach)
            ->get(route('admin.coaches.edit', $other))
            ->assertForbidden();

        $this->actingAs($coach)
            ->put(route('admin.coaches.update', $other), [
                'name' => 'Hacked',
                'username' => $other->username,
            ])
            ->assertForbidden();
    }

    public function test_profile_image_is_stored_with_a_safe_filename(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->withProfile()->create();
        $file = UploadedFile::fake()->image('photo.jpg', 80, 80);

        $this->actingAs($admin)->post(route('admin.coaches.store'), [
            'name' => 'Ahmad Hassan',
            'username' => 'ahmad',
            'password' => 'password',
            'password_confirmation' => 'password',
            'is_active' => '1',
            'is_available' => '1',
            'profile_image' => $file,
        ]);

        $coach = User::query()->where('username', 'ahmad')->first();

        $this->assertNotNull($coach?->coachProfile?->profile_image);
        $this->assertStringStartsWith('coaches/', $coach->coachProfile->profile_image);
        $this->assertStringNotContainsString('photo.jpg', $coach->coachProfile->profile_image);
        Storage::disk('public')->assertExists($coach->coachProfile->profile_image);
    }
}
