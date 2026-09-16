<?php

namespace Tests\Feature;

use App\Enums\ClientPackageStatus;
use App\Enums\SessionBalanceStatus;
use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Package;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EndToEndFlowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_scenario_a_new_client_package_assignment(): void
    {
        $admin = User::factory()->admin()->withProfile()->create([
            'username' => 'admin',
            'password' => 'password',
        ]);
        $catalog = Package::factory()->create([
            'name' => '8 Sessions',
            'sessions_count' => 8,
            'price' => '150.00',
        ]);

        $this->post(route('login.store'), [
            'username' => 'admin',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->post(route('admin.clients.store'), [
            'full_name' => 'John Smith',
            'phone' => '+961 70 555 001',
            'status' => 'active',
        ])->assertRedirect();

        $client = Client::query()->where('full_name', 'John Smith')->firstOrFail();

        $this->get(route('admin.clients.index'))
            ->assertOk()
            ->assertSee('John Smith')
            ->assertSee('No Package');

        $this->get(route('admin.clients.index', ['search' => 'John Smith']))
            ->assertOk()
            ->assertSee('John Smith');

        $this->get(route('admin.clients.index', ['balance' => 'none']))
            ->assertOk()
            ->assertSee('John Smith');

        $this->assertSame(SessionBalanceStatus::NoPackage, $client->sessionBalanceStatus());

        $this->post(route('admin.clients.packages.store', $client), [
            'package_id' => $catalog->id,
            'purchased_sessions' => 8,
            'price_paid' => '150.00',
            'starts_at' => today()->toDateString(),
        ])
            ->assertRedirect(route('admin.clients.show', $client))
            ->assertSessionHas('success', 'Package assigned successfully.');

        $client->refresh()->load('currentPackage', 'clientPackages');

        $this->assertSame(8, $client->currentPackage?->purchased_sessions);
        $this->assertSame(0, $client->currentPackage?->used_sessions);
        $this->assertSame(8, $client->currentPackage?->remaining_sessions);
        $this->assertSame(1, $client->clientPackages->count());

        $this->get(route('admin.clients.show', $client))
            ->assertOk()
            ->assertSee('Purchased')
            ->assertSee('8 Sessions');
    }

    public function test_scenario_e_recharge_preserves_history_and_old_sessions(): void
    {
        $admin = User::factory()->admin()->withProfile()->create();
        $coach = User::factory()->coach()->withProfile()->create();
        $client = Client::factory()->create();
        $eight = Package::factory()->create([
            'name' => '8 Sessions',
            'sessions_count' => 8,
            'price' => '150.00',
        ]);
        $twelve = Package::factory()->create([
            'name' => '12 Sessions',
            'sessions_count' => 12,
            'price' => '250.00',
        ]);

        $this->actingAs($admin)->post(route('admin.clients.packages.store', $client), [
            'package_id' => $eight->id,
            'purchased_sessions' => 8,
            'price_paid' => '150.00',
            'starts_at' => today()->subWeeks(2)->toDateString(),
        ]);

        $oldPackage = $client->fresh()->currentPackage;
        $this->assertNotNull($oldPackage);

        $this->actingAs($admin)->post(route('admin.schedule.sessions.store'), [
            'client_id' => $client->id,
            'coach_user_id' => $coach->id,
            'session_date' => today()->subDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
        ]);

        $session = TrainingSession::query()->firstOrFail();

        $oldPackage->update(['used_sessions' => 7]);

        $this->actingAs($admin)
            ->post(route('admin.schedule.sessions.done', $session))
            ->assertRedirect();

        $oldPackage->refresh();
        $this->assertSame(8, $oldPackage->used_sessions);
        $this->assertSame(0, $oldPackage->remaining_sessions);
        $this->assertSame(ClientPackageStatus::Completed, $oldPackage->status);
        $this->assertSame(SessionBalanceStatus::RechargeRequired, $client->fresh()->load('currentPackage')->sessionBalanceStatus());

        $this->actingAs($admin)->post(route('admin.clients.packages.store', $client), [
            'package_id' => $twelve->id,
            'purchased_sessions' => 12,
            'price_paid' => '250.00',
            'starts_at' => today()->toDateString(),
        ])->assertRedirect(route('admin.clients.show', $client));

        $client->refresh()->load('currentPackage', 'clientPackages');
        $newPackage = $client->currentPackage;

        $this->assertNotNull($newPackage);
        $this->assertNotSame($oldPackage->id, $newPackage->id);
        $this->assertSame(12, $newPackage->purchased_sessions);
        $this->assertSame(0, $newPackage->used_sessions);
        $this->assertSame(12, $newPackage->remaining_sessions);
        $this->assertSame(ClientPackageStatus::Active, $newPackage->status);
        $this->assertSame(ClientPackageStatus::Completed, $oldPackage->fresh()->status);
        $this->assertSame(2, $client->clientPackages()->count());
        $this->assertSame($oldPackage->id, $session->fresh()->client_package_id);
    }

    public function test_user_role_cannot_be_mass_assigned(): void
    {
        $admin = User::factory()->admin()->create();

        $admin->fill([
            'name' => 'Still Admin',
            'role' => UserRole::Coach->value,
        ])->save();

        $this->assertSame('Still Admin', $admin->fresh()->name);
        $this->assertTrue($admin->fresh()->isAdmin());
    }

    public function test_dashboard_uses_live_session_counts(): void
    {
        $admin = User::factory()->admin()->withProfile()->create();
        $coach = User::factory()->coach()->withProfile()->create();
        $client = Client::factory()->create();
        $catalog = Package::factory()->create();

        $this->actingAs($admin)->post(route('admin.clients.packages.store', $client), [
            'package_id' => $catalog->id,
            'purchased_sessions' => 8,
            'price_paid' => '150.00',
            'starts_at' => today()->toDateString(),
        ]);

        $this->actingAs($admin)->post(route('admin.schedule.sessions.store'), [
            'client_id' => $client->id,
            'coach_user_id' => $coach->id,
            'session_date' => today()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Sessions Today')
            ->assertSee('Pending Today')
            ->assertSee($client->full_name);
    }
}
