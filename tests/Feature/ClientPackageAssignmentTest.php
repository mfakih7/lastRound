<?php

namespace Tests\Feature;

use App\Enums\ClientPackageStatus;
use App\Enums\SessionBalanceStatus;
use App\Models\Client;
use App\Models\ClientPackage;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientPackageAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_assign_package_with_snapshotted_values(): void
    {
        $admin = User::factory()->admin()->create();
        $client = Client::factory()->create();
        $package = Package::factory()->create([
            'name' => '12 Sessions',
            'sessions_count' => 12,
            'price' => '250.00',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.clients.packages.store', $client), [
                'package_id' => $package->id,
                'purchased_sessions' => 12,
                'price_paid' => '250.00',
                'starts_at' => now()->toDateString(),
            ])
            ->assertRedirect(route('admin.clients.show', $client))
            ->assertSessionHas('success');

        $purchase = $client->currentPackage()->first();

        $this->assertNotNull($purchase);
        $this->assertSame('12 Sessions', $purchase->package_name);
        $this->assertSame(12, $purchase->purchased_sessions);
        $this->assertSame('250.00', $purchase->price_paid);
        $this->assertSame(0, $purchase->used_sessions);
        $this->assertSame(12, $purchase->remaining_sessions);
        $this->assertSame(ClientPackageStatus::Active, $purchase->status);
    }

    public function test_remaining_sessions_never_go_negative(): void
    {
        $purchase = ClientPackage::factory()->create([
            'purchased_sessions' => 8,
            'used_sessions' => 12,
        ]);

        $this->assertSame(0, $purchase->remaining_sessions);
    }

    public function test_historical_package_remains_after_recharge(): void
    {
        $admin = User::factory()->admin()->create();
        $client = Client::factory()->create();
        $first = Package::factory()->create(['name' => '12 Sessions', 'sessions_count' => 12, 'price' => '250.00']);
        $second = Package::factory()->create(['name' => '8 Sessions', 'sessions_count' => 8, 'price' => '150.00']);

        $original = ClientPackage::factory()->for($client)->forPackage($first)->withUsed(12)->create();

        $this->actingAs($admin)->post(route('admin.clients.packages.store', $client), [
            'package_id' => $second->id,
            'purchased_sessions' => 8,
            'price_paid' => '150.00',
            'starts_at' => now()->toDateString(),
        ])->assertRedirect(route('admin.clients.show', $client));

        $this->assertSame(ClientPackageStatus::Completed, $original->fresh()->status);
        $this->assertSame(2, $client->clientPackages()->count());
        $this->assertSame('8 Sessions', $client->fresh()->currentPackage->package_name);
        $this->assertSame('12 Sessions', $original->fresh()->package_name);
    }

    public function test_catalog_price_change_does_not_alter_historical_purchase(): void
    {
        $client = Client::factory()->create();
        $package = Package::factory()->create(['name' => '12 Sessions', 'sessions_count' => 12, 'price' => '250.00']);
        $purchase = ClientPackage::factory()->for($client)->forPackage($package)->create();

        $package->update(['price' => '270.00', 'sessions_count' => 14]);

        $purchase->refresh();

        $this->assertSame('250.00', $purchase->price_paid);
        $this->assertSame(12, $purchase->purchased_sessions);
        $this->assertSame('12 Sessions', $purchase->package_name);
    }

    public function test_client_cannot_have_two_active_packages(): void
    {
        $admin = User::factory()->admin()->create();
        $client = Client::factory()->create();
        $first = Package::factory()->create(['name' => '12 Sessions', 'sessions_count' => 12, 'price' => '250.00']);
        $second = Package::factory()->create(['name' => '8 Sessions', 'sessions_count' => 8, 'price' => '150.00']);

        ClientPackage::factory()->for($client)->forPackage($first)->create();

        $this->actingAs($admin)
            ->from(route('admin.clients.packages.create', $client))
            ->post(route('admin.clients.packages.store', $client), [
                'package_id' => $second->id,
                'purchased_sessions' => 8,
                'price_paid' => '150.00',
                'starts_at' => now()->toDateString(),
            ])
            ->assertRedirect(route('admin.clients.packages.create', $client))
            ->assertSessionHasErrors([
                'package_id' => 'This client still has 12 sessions remaining in the current package.',
            ]);

        $this->assertSame(1, $client->clientPackages()->where('status', ClientPackageStatus::Active)->count());
    }

    public function test_completed_package_is_not_returned_as_current(): void
    {
        $client = Client::factory()->create();
        $package = Package::factory()->create();

        ClientPackage::factory()->for($client)->forPackage($package)->completed()->create();

        $this->assertNull($client->fresh()->currentPackage);
        $this->assertSame(SessionBalanceStatus::RechargeRequired, $client->fresh()->sessionBalanceStatus());
    }

    public function test_low_session_and_recharge_statuses_are_calculated_correctly(): void
    {
        $low = Client::factory()->create();
        $empty = Client::factory()->create();
        $healthy = Client::factory()->create();
        $none = Client::factory()->create();
        $package = Package::factory()->create(['sessions_count' => 12, 'price' => '250.00']);

        ClientPackage::factory()->for($low)->forPackage($package)->withUsed(10)->create();
        ClientPackage::factory()->for($empty)->forPackage($package)->withUsed(12)->create();
        ClientPackage::factory()->for($healthy)->forPackage($package)->withUsed(5)->create();

        $this->assertSame(2, $low->load('currentPackage')->remainingSessions());
        $this->assertSame(SessionBalanceStatus::LowSessions, $low->sessionBalanceStatus(2));
        $this->assertSame(SessionBalanceStatus::RechargeRequired, $empty->load('currentPackage')->sessionBalanceStatus(2));
        $this->assertSame(0, $empty->remainingSessions());
        $this->assertSame(SessionBalanceStatus::Healthy, $healthy->load('currentPackage')->sessionBalanceStatus(2));
        $this->assertSame(SessionBalanceStatus::NoPackage, $none->sessionBalanceStatus(2));
    }

    public function test_listing_shows_package_balance_and_coach_cannot_assign(): void
    {
        $admin = User::factory()->admin()->create();
        $coach = User::factory()->coach()->create();
        $client = Client::factory()->create(['full_name' => 'Karim Haddad']);
        $package = Package::factory()->create(['name' => '8 Sessions']);
        ClientPackage::factory()->for($client)->forPackage($package)->withUsed(8)->create();

        $this->actingAs($admin)
            ->get(route('admin.clients.index'))
            ->assertOk()
            ->assertSee('Karim Haddad')
            ->assertSee('8 Sessions')
            ->assertSee('Recharge Required');

        $this->actingAs($coach)
            ->post(route('admin.clients.packages.store', $client), [
                'package_id' => $package->id,
                'purchased_sessions' => 8,
                'price_paid' => '150.00',
                'starts_at' => now()->toDateString(),
            ])
            ->assertForbidden();
    }
}
