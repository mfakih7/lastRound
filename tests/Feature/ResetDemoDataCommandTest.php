<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\ClientPackage;
use App\Models\Package;
use App\Models\Setting;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResetDemoDataCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_clears_business_data_and_preserves_admin_login(): void
    {
        $dataset = $this->createBusinessDataset();

        $this->artisan('lastround:reset-demo-data', ['--force' => true])
            ->expectsOutputToContain('training_sessions')
            ->expectsOutputToContain('Demo data reset complete')
            ->assertSuccessful();

        $this->assertDatabaseCount('training_sessions', 0);
        $this->assertDatabaseCount('client_packages', 0);
        $this->assertDatabaseCount('clients', 0);
        $this->assertDatabaseCount('packages', 0);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseMissing('users', ['id' => $dataset['coach']->id]);
        $this->assertDatabaseMissing('coach_profiles', ['user_id' => $dataset['coach']->id]);

        $admin = $dataset['admin']->fresh();

        $this->assertNotNull($admin);
        $this->assertSame('headcoach', $admin->username);
        $this->assertSame('keep-me@lastround.test', $admin->email);
        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertSame($dataset['passwordHash'], $admin->getRawOriginal('password'));
        $this->assertTrue(Hash::check('KeepThisHash!9', $admin->password));
        $this->assertNotNull($admin->coachProfile);
        $this->assertSame('Head coach notes stay.', $admin->coachProfile->notes);
        $this->assertSame('LastRound Gym', Setting::query()->where('key', 'app_name')->value('value'));
        $this->assertSame('3', Setting::query()->where('key', 'low_session_warning_threshold')->value('value'));

        $response = $this->post(route('login.store'), [
            'username' => 'headcoach',
            'password' => 'KeepThisHash!9',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_dry_run_reports_counts_without_deleting_records(): void
    {
        $dataset = $this->createBusinessDataset();

        $this->artisan('lastround:reset-demo-data', ['--dry-run' => true])
            ->expectsOutputToContain('Dry run')
            ->expectsOutputToContain('No records were deleted')
            ->assertSuccessful();

        $this->assertModelExists($dataset['admin']);
        $this->assertModelExists($dataset['coach']);
        $this->assertModelExists($dataset['client']);
        $this->assertModelExists($dataset['package']);
        $this->assertModelExists($dataset['purchase']);
        $this->assertModelExists($dataset['session']);
        $this->assertDatabaseCount('clients', 1);
        $this->assertDatabaseCount('training_sessions', 1);
    }

    public function test_cancels_when_confirmation_is_declined(): void
    {
        $dataset = $this->createBusinessDataset();

        $this->artisan('lastround:reset-demo-data')
            ->expectsConfirmation('Delete all demo/business data while preserving the admin account?', 'no')
            ->expectsOutputToContain('Command cancelled')
            ->assertFailed();

        $this->assertModelExists($dataset['client']);
        $this->assertModelExists($dataset['coach']);
        $this->assertDatabaseCount('training_sessions', 1);
    }

    public function test_fails_when_no_admin_user_exists(): void
    {
        User::factory()->coach()->withProfile()->create();
        Client::factory()->create();

        $this->artisan('lastround:reset-demo-data', ['--force' => true])
            ->expectsOutputToContain('No admin user exists')
            ->assertFailed();

        $this->assertDatabaseCount('clients', 1);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_requires_confirmation_in_production(): void
    {
        $this->app['env'] = 'production';
        $dataset = $this->createBusinessDataset();

        $this->artisan('lastround:reset-demo-data')
            ->expectsConfirmation('Application is in production. Permanently delete all demo/business data while keeping the admin account?', 'no')
            ->expectsOutputToContain('This permanently deletes clients, coaches, packages, purchases, and sessions.')
            ->expectsOutputToContain('Command cancelled')
            ->assertFailed();

        $this->assertModelExists($dataset['client']);
        $this->assertModelExists($dataset['coach']);
    }

    public function test_deletes_non_admin_password_reset_tokens_and_profile_images(): void
    {
        Storage::fake('public');
        $dataset = $this->createBusinessDataset();

        $dataset['coach']->coachProfile->update([
            'profile_image' => 'coaches/marcus.jpg',
        ]);
        Storage::disk('public')->put('coaches/marcus.jpg', 'image');

        DB::table('password_reset_tokens')->insert([
            [
                'email' => $dataset['admin']->email,
                'token' => 'admin-token',
                'created_at' => now(),
            ],
            [
                'email' => $dataset['coach']->email,
                'token' => 'coach-token',
                'created_at' => now(),
            ],
        ]);

        $this->artisan('lastround:reset-demo-data', ['--force' => true])
            ->assertSuccessful();

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'keep-me@lastround.test',
        ]);
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $dataset['coach']->email,
        ]);
        Storage::disk('public')->assertMissing('coaches/marcus.jpg');

        if (Schema::hasTable('sessions')) {
            $this->assertDatabaseHas('sessions', [
                'id' => 'admin-session',
                'user_id' => $dataset['admin']->id,
            ]);
            $this->assertDatabaseMissing('sessions', [
                'id' => 'coach-session',
            ]);
        }
    }

    /**
     * @return array{
     *     admin: User,
     *     coach: User,
     *     client: Client,
     *     package: Package,
     *     purchase: ClientPackage,
     *     session: TrainingSession,
     *     passwordHash: string
     * }
     */
    protected function createBusinessDataset(): array
    {
        $admin = User::factory()->admin()->withProfile([
            'notes' => 'Head coach notes stay.',
            'is_available' => true,
        ])->create([
            'name' => 'Custom Head Coach',
            'username' => 'headcoach',
            'email' => 'keep-me@lastround.test',
            'password' => 'KeepThisHash!9',
        ]);

        $coach = User::factory()->coach()->withProfile()->create([
            'username' => 'marcus',
            'email' => 'marcus@lastround.test',
        ]);

        $package = Package::factory()->create([
            'name' => '8 Sessions',
            'sessions_count' => 8,
            'price' => '150.00',
        ]);

        $client = Client::factory()->forCoach($coach)->create([
            'full_name' => 'John Smith',
        ]);

        $purchase = ClientPackage::factory()->forPackage($package)->create([
            'client_id' => $client->id,
        ]);

        $session = TrainingSession::factory()->create([
            'client_id' => $client->id,
            'coach_user_id' => $admin->id,
            'client_package_id' => $purchase->id,
        ]);

        Setting::query()->create([
            'key' => 'app_name',
            'value' => 'LastRound Gym',
            'type' => 'string',
        ]);
        Setting::query()->create([
            'key' => 'low_session_warning_threshold',
            'value' => '3',
            'type' => 'integer',
        ]);

        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->insert([
                [
                    'id' => 'admin-session',
                    'user_id' => $admin->id,
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'phpunit',
                    'payload' => 'admin',
                    'last_activity' => time(),
                ],
                [
                    'id' => 'coach-session',
                    'user_id' => $coach->id,
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'phpunit',
                    'payload' => 'coach',
                    'last_activity' => time(),
                ],
            ]);
        }

        return [
            'admin' => $admin,
            'coach' => $coach,
            'client' => $client,
            'package' => $package,
            'purchase' => $purchase,
            'session' => $session,
            'passwordHash' => $admin->getRawOriginal('password'),
        ];
    }
}
