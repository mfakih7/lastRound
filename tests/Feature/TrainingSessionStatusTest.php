<?php

namespace Tests\Feature;

use App\Enums\ClientPackageStatus;
use App\Enums\SessionBalanceStatus;
use App\Enums\TrainingSessionStatus;
use App\Models\Client;
use App\Models\ClientPackage;
use App\Models\Package;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainingSessionStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_to_done_consumes_one_session(): void
    {
        [$admin, $session, $package] = $this->pendingSession();

        $this->actingAs($admin)
            ->post(route('admin.schedule.sessions.done', $session))
            ->assertRedirect();

        $this->assertSame(TrainingSessionStatus::Done, $session->fresh()->status);
        $this->assertSame(1, $package->fresh()->used_sessions);
        $this->assertSame(7, $package->fresh()->remaining_sessions);
    }

    public function test_done_to_done_does_not_deduct_again(): void
    {
        [$admin, $session, $package] = $this->pendingSession();
        $this->actingAs($admin)->post(route('admin.schedule.sessions.done', $session));

        $this->actingAs($admin)
            ->put(route('admin.schedule.sessions.update', $session), $this->updatePayload($session, [
                'status' => TrainingSessionStatus::Done->value,
                'notes' => 'Edited after done',
            ]))
            ->assertRedirect();

        $this->assertSame(1, $package->fresh()->used_sessions);
        $this->assertSame('Edited after done', $session->fresh()->notes);
    }

    public function test_done_to_cancelled_restores_one_session(): void
    {
        [$admin, $session, $package] = $this->pendingSession();
        $this->actingAs($admin)->post(route('admin.schedule.sessions.done', $session));

        $this->actingAs($admin)
            ->post(route('admin.schedule.sessions.cancel', $session))
            ->assertRedirect();

        $this->assertSame(TrainingSessionStatus::Cancelled, $session->fresh()->status);
        $this->assertSame(0, $package->fresh()->used_sessions);
        $this->assertSame(8, $package->fresh()->remaining_sessions);
    }

    public function test_done_to_pending_restores_one_session(): void
    {
        [$admin, $session, $package] = $this->pendingSession();
        $this->actingAs($admin)->post(route('admin.schedule.sessions.done', $session));

        $this->actingAs($admin)
            ->put(route('admin.schedule.sessions.update', $session), $this->updatePayload($session, [
                'status' => TrainingSessionStatus::Pending->value,
            ]))
            ->assertRedirect();

        $this->assertSame(TrainingSessionStatus::Pending, $session->fresh()->status);
        $this->assertSame(0, $package->fresh()->used_sessions);
    }

    public function test_pending_to_cancelled_does_not_change_balance(): void
    {
        [$admin, $session, $package] = $this->pendingSession();

        $this->actingAs($admin)->post(route('admin.schedule.sessions.cancel', $session));

        $this->assertSame(0, $package->fresh()->used_sessions);
        $this->assertSame(TrainingSessionStatus::Cancelled, $session->fresh()->status);
    }

    public function test_cancelled_to_pending_does_not_change_balance(): void
    {
        [$admin, $session, $package] = $this->pendingSession();
        $this->actingAs($admin)->post(route('admin.schedule.sessions.cancel', $session));

        $this->actingAs($admin)
            ->put(route('admin.schedule.sessions.update', $session), $this->updatePayload($session, [
                'status' => TrainingSessionStatus::Pending->value,
            ]))
            ->assertRedirect();

        $this->assertSame(0, $package->fresh()->used_sessions);
        $this->assertSame(TrainingSessionStatus::Pending, $session->fresh()->status);
    }

    public function test_cancelled_to_done_consumes_one_session(): void
    {
        [$admin, $session, $package] = $this->pendingSession();
        $this->actingAs($admin)->post(route('admin.schedule.sessions.cancel', $session));

        $this->actingAs($admin)
            ->put(route('admin.schedule.sessions.update', $session), $this->updatePayload($session, [
                'status' => TrainingSessionStatus::Done->value,
            ]))
            ->assertRedirect();

        $this->assertSame(TrainingSessionStatus::Done, $session->fresh()->status);
        $this->assertSame(1, $package->fresh()->used_sessions);
    }

    public function test_final_session_completes_package_and_reversing_reactivates_it(): void
    {
        [$admin, $session, $package] = $this->pendingSession(purchased: 8, used: 7);
        $client = $session->client;

        $this->actingAs($admin)->post(route('admin.schedule.sessions.done', $session));

        $package->refresh();
        $this->assertSame(8, $package->used_sessions);
        $this->assertSame(0, $package->remaining_sessions);
        $this->assertSame(ClientPackageStatus::Completed, $package->status);
        $this->assertSame(SessionBalanceStatus::RechargeRequired, $client->fresh()->sessionBalanceStatus());
        $this->assertNull($client->fresh()->currentPackage);

        $this->actingAs($admin)
            ->get(route('admin.clients.show', $client))
            ->assertOk()
            ->assertSee('Recharge Required');

        $this->actingAs($admin)->post(route('admin.schedule.sessions.cancel', $session));

        $package->refresh();
        $this->assertSame(7, $package->used_sessions);
        $this->assertSame(1, $package->remaining_sessions);
        $this->assertSame(ClientPackageStatus::Active, $package->status);
        $this->assertSame(SessionBalanceStatus::LowSessions, $client->fresh()->load('currentPackage')->sessionBalanceStatus());
    }

    public function test_historical_session_stays_linked_to_original_package_after_recharge(): void
    {
        [$admin, $session, $packageA] = $this->pendingSession(purchased: 8, used: 7);
        $client = $session->client;

        $this->actingAs($admin)->post(route('admin.schedule.sessions.done', $session));

        $packageB = Package::factory()->create(['name' => '12 Sessions', 'sessions_count' => 12, 'price' => '250.00']);

        $this->actingAs($admin)->post(route('admin.clients.packages.store', $client), [
            'package_id' => $packageB->id,
            'purchased_sessions' => 12,
            'price_paid' => '250.00',
            'starts_at' => now()->toDateString(),
        ])->assertRedirect();

        $this->assertSame($packageA->id, $session->fresh()->client_package_id);
        $this->assertSame('8 Sessions', $session->fresh()->clientPackage->displayName());
        $this->assertSame('12 Sessions', $client->fresh()->currentPackage->displayName());
    }

    public function test_done_sessions_cannot_be_deleted(): void
    {
        [$admin, $session] = $this->pendingSession();
        $this->actingAs($admin)->post(route('admin.schedule.sessions.done', $session));

        $this->actingAs($admin)
            ->from(route('admin.schedule.sessions.show', $session))
            ->delete(route('admin.schedule.sessions.destroy', $session))
            ->assertRedirect(route('admin.schedule.sessions.show', $session))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('training_sessions', ['id' => $session->id]);
    }

    public function test_pending_sessions_can_be_deleted(): void
    {
        [$admin, $session] = $this->pendingSession();

        $this->actingAs($admin)
            ->delete(route('admin.schedule.sessions.destroy', $session))
            ->assertRedirect();

        $this->assertDatabaseMissing('training_sessions', ['id' => $session->id]);
    }

    public function test_done_cannot_consume_beyond_purchased_sessions(): void
    {
        $admin = User::factory()->admin()->withProfile()->create();
        $coach = User::factory()->coach()->withProfile()->create();
        $client = Client::factory()->create();
        $package = ClientPackage::factory()->for($client)->create([
            'purchased_sessions' => 8,
            'used_sessions' => 8,
            'status' => ClientPackageStatus::Active,
        ]);
        $session = TrainingSession::factory()->create([
            'client_id' => $client->id,
            'coach_user_id' => $coach->id,
            'client_package_id' => $package->id,
            'status' => TrainingSessionStatus::Pending,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.schedule.sessions.show', $session))
            ->post(route('admin.schedule.sessions.done', $session))
            ->assertRedirect(route('admin.schedule.sessions.show', $session))
            ->assertSessionHas('error');

        $this->assertSame(8, $package->fresh()->used_sessions);
        $this->assertSame(TrainingSessionStatus::Pending, $session->fresh()->status);
    }

    public function test_restoring_done_never_makes_used_sessions_negative(): void
    {
        $admin = User::factory()->admin()->withProfile()->create();
        $coach = User::factory()->coach()->withProfile()->create();
        $client = Client::factory()->create();
        $package = ClientPackage::factory()->for($client)->create([
            'purchased_sessions' => 8,
            'used_sessions' => 0,
            'status' => ClientPackageStatus::Active,
        ]);
        $session = TrainingSession::factory()->create([
            'client_id' => $client->id,
            'coach_user_id' => $coach->id,
            'client_package_id' => $package->id,
            'status' => TrainingSessionStatus::Done,
        ]);

        $this->actingAs($admin)->post(route('admin.schedule.sessions.cancel', $session));

        $this->assertSame(0, $package->fresh()->used_sessions);
        $this->assertSame(TrainingSessionStatus::Cancelled, $session->fresh()->status);
    }

    /**
     * @return array{0: User, 1: TrainingSession, 2: ClientPackage}
     */
    protected function pendingSession(int $purchased = 8, int $used = 0): array
    {
        $admin = User::factory()->admin()->withProfile()->create();
        $coach = User::factory()->coach()->withProfile()->create();
        $client = Client::factory()->create();
        $catalog = Package::factory()->create([
            'name' => $purchased.' Sessions',
            'sessions_count' => $purchased,
            'price' => '150.00',
        ]);
        $package = ClientPackage::factory()->for($client)->forPackage($catalog)->create([
            'purchased_sessions' => $purchased,
            'used_sessions' => $used,
            'status' => ClientPackageStatus::Active,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.schedule.sessions.store'), [
                'client_id' => $client->id,
                'coach_user_id' => $coach->id,
                'session_date' => today()->addDay()->toDateString(),
                'start_time' => '18:00',
                'end_time' => '19:00',
            ]);

        $session = TrainingSession::query()->firstOrFail();

        return [$admin, $session, $package->fresh()];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function updatePayload(TrainingSession $session, array $overrides = []): array
    {
        return array_merge([
            'client_id' => $session->client_id,
            'coach_user_id' => $session->coach_user_id,
            'session_date' => $session->session_date->toDateString(),
            'start_time' => $session->formattedStartTime(),
            'end_time' => $session->formattedEndTime(),
            'status' => $session->status->value,
            'notes' => $session->notes,
        ], $overrides);
    }
}
