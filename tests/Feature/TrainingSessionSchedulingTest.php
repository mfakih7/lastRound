<?php

namespace Tests\Feature;

use App\Enums\ClientPackageStatus;
use App\Enums\ClientStatus;
use App\Enums\TrainingSessionStatus;
use App\Models\Client;
use App\Models\ClientPackage;
use App\Models\Package;
use App\Models\TrainingSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainingSessionSchedulingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_session_linked_to_current_package(): void
    {
        $admin = User::factory()->admin()->withProfile()->create();
        $coach = User::factory()->coach()->withProfile()->create(['name' => 'Ahmad']);
        $client = $this->readyClient();
        $package = $client->currentPackage;

        $this->actingAs($admin)
            ->post(route('admin.schedule.sessions.store'), $this->payload($client, $coach, [
                'notes' => 'Focus on jabs.',
            ]))
            ->assertRedirect();

        $session = TrainingSession::query()->first();

        $this->assertNotNull($session);
        $this->assertSame($package->id, $session->client_package_id);
        $this->assertSame(TrainingSessionStatus::Pending, $session->status);
        $this->assertSame(0, $package->fresh()->used_sessions);
        $this->assertSame(7, $package->fresh()->unreservedSessions());
    }

    public function test_client_without_package_cannot_be_scheduled(): void
    {
        $admin = User::factory()->admin()->withProfile()->create();
        $coach = User::factory()->coach()->withProfile()->create();
        $client = Client::factory()->create();

        $this->actingAs($admin)
            ->from(route('admin.schedule.sessions.create'))
            ->post(route('admin.schedule.sessions.store'), $this->payload($client, $coach))
            ->assertRedirect(route('admin.schedule.sessions.create'))
            ->assertSessionHasErrors('client_id');
    }

    public function test_zero_remaining_sessions_cannot_be_scheduled(): void
    {
        $admin = User::factory()->admin()->withProfile()->create();
        $coach = User::factory()->coach()->withProfile()->create();
        $client = $this->readyClient(used: 8);

        $this->actingAs($admin)
            ->from(route('admin.schedule.sessions.create'))
            ->post(route('admin.schedule.sessions.store'), $this->payload($client, $coach))
            ->assertSessionHasErrors('client_id');
    }

    public function test_pending_sessions_reserve_capacity_and_cancelled_release_it(): void
    {
        $admin = User::factory()->admin()->withProfile()->create();
        $coach = User::factory()->coach()->withProfile()->create();
        $client = $this->readyClient(purchased: 2);

        $this->actingAs($admin)
            ->post(route('admin.schedule.sessions.store'), $this->payload($client, $coach, [
                'start_time' => '09:00',
                'end_time' => '10:00',
            ]))
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('admin.schedule.sessions.store'), $this->payload($client, $coach, [
                'start_time' => '11:00',
                'end_time' => '12:00',
            ]))
            ->assertRedirect();

        $this->actingAs($admin)
            ->from(route('admin.schedule.sessions.create'))
            ->post(route('admin.schedule.sessions.store'), $this->payload($client, $coach, [
                'start_time' => '13:00',
                'end_time' => '14:00',
            ]))
            ->assertSessionHasErrors('client_id');

        $first = TrainingSession::query()->orderBy('id')->first();

        $this->actingAs($admin)
            ->post(route('admin.schedule.sessions.cancel', $first))
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('admin.schedule.sessions.store'), $this->payload($client, $coach, [
                'start_time' => '13:00',
                'end_time' => '14:00',
            ]))
            ->assertRedirect();

        $this->assertSame(2, TrainingSession::query()->where('status', TrainingSessionStatus::Pending)->count());
        $this->assertSame(1, TrainingSession::query()->where('status', TrainingSessionStatus::Cancelled)->count());
    }

    public function test_coach_overlap_is_rejected_and_boundary_is_allowed(): void
    {
        $admin = User::factory()->admin()->withProfile()->create();
        $coach = User::factory()->coach()->withProfile()->create(['name' => 'Ahmad']);
        $clientA = $this->readyClient();
        $clientB = $this->readyClient();

        $this->actingAs($admin)
            ->post(route('admin.schedule.sessions.store'), $this->payload($clientA, $coach, [
                'start_time' => '18:00',
                'end_time' => '19:00',
            ]))
            ->assertRedirect();

        $this->actingAs($admin)
            ->from(route('admin.schedule.sessions.create'))
            ->post(route('admin.schedule.sessions.store'), $this->payload($clientB, $coach, [
                'start_time' => '18:30',
                'end_time' => '19:30',
            ]))
            ->assertSessionHasErrors('coach_user_id');

        $this->actingAs($admin)
            ->post(route('admin.schedule.sessions.store'), $this->payload($clientB, $coach, [
                'start_time' => '17:00',
                'end_time' => '18:00',
            ]))
            ->assertRedirect();
    }

    public function test_client_overlap_is_rejected(): void
    {
        $admin = User::factory()->admin()->withProfile()->create();
        $coachA = User::factory()->coach()->withProfile()->create();
        $coachB = User::factory()->coach()->withProfile()->create();
        $client = $this->readyClient();

        $this->actingAs($admin)
            ->post(route('admin.schedule.sessions.store'), $this->payload($client, $coachA, [
                'start_time' => '18:00',
                'end_time' => '19:00',
            ]))
            ->assertRedirect();

        $this->actingAs($admin)
            ->from(route('admin.schedule.sessions.create'))
            ->post(route('admin.schedule.sessions.store'), $this->payload($client, $coachB, [
                'start_time' => '18:30',
                'end_time' => '19:30',
            ]))
            ->assertSessionHasErrors('client_id');
    }

    public function test_cancelled_session_does_not_block_slot(): void
    {
        $admin = User::factory()->admin()->withProfile()->create();
        $coach = User::factory()->coach()->withProfile()->create();
        $client = $this->readyClient();

        $this->actingAs($admin)
            ->post(route('admin.schedule.sessions.store'), $this->payload($client, $coach))
            ->assertRedirect();

        $session = TrainingSession::query()->first();
        $this->actingAs($admin)->post(route('admin.schedule.sessions.cancel', $session));

        $other = $this->readyClient();

        $this->actingAs($admin)
            ->post(route('admin.schedule.sessions.store'), $this->payload($other, $coach))
            ->assertRedirect();

        $this->assertSame(2, TrainingSession::query()->count());
    }

    public function test_editing_a_session_excludes_itself_from_conflict_checks(): void
    {
        $admin = User::factory()->admin()->withProfile()->create();
        $coach = User::factory()->coach()->withProfile()->create();
        $client = $this->readyClient();

        $this->actingAs($admin)
            ->post(route('admin.schedule.sessions.store'), $this->payload($client, $coach, [
                'notes' => 'Original',
            ]))
            ->assertRedirect();

        $session = TrainingSession::query()->first();

        $this->actingAs($admin)
            ->put(route('admin.schedule.sessions.update', $session), $this->payload($client, $coach, [
                'status' => TrainingSessionStatus::Pending->value,
                'notes' => 'Updated notes',
            ]))
            ->assertRedirect(route('admin.schedule.sessions.show', $session));

        $this->assertSame('Updated notes', $session->fresh()->notes);
    }

    public function test_different_coaches_can_train_at_the_same_time(): void
    {
        $admin = User::factory()->admin()->withProfile()->create();
        $coachA = User::factory()->coach()->withProfile()->create();
        $coachB = User::factory()->coach()->withProfile()->create();
        $clientA = $this->readyClient();
        $clientB = $this->readyClient();

        $this->actingAs($admin)
            ->post(route('admin.schedule.sessions.store'), $this->payload($clientA, $coachA))
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('admin.schedule.sessions.store'), $this->payload($clientB, $coachB))
            ->assertRedirect();

        $this->assertSame(2, TrainingSession::query()->count());
    }

    public function test_admin_head_coach_can_be_scheduled_and_has_conflicts(): void
    {
        $admin = User::factory()->admin()->withProfile()->create(['name' => 'Mohamad']);
        $clientA = $this->readyClient();
        $clientB = $this->readyClient();

        $this->actingAs($admin)
            ->get(route('admin.schedule.sessions.create'))
            ->assertOk()
            ->assertSee('Mohamad — Head Coach');

        $this->actingAs($admin)
            ->post(route('admin.schedule.sessions.store'), $this->payload($clientA, $admin))
            ->assertRedirect();

        $this->actingAs($admin)
            ->from(route('admin.schedule.sessions.create'))
            ->post(route('admin.schedule.sessions.store'), $this->payload($clientB, $admin, [
                'start_time' => '18:30',
                'end_time' => '19:30',
            ]))
            ->assertSessionHasErrors('coach_user_id');
    }

    public function test_inactive_client_cannot_be_scheduled(): void
    {
        $admin = User::factory()->admin()->withProfile()->create();
        $coach = User::factory()->coach()->withProfile()->create();
        $client = $this->readyClient();
        $client->update(['status' => ClientStatus::Inactive]);

        $this->actingAs($admin)
            ->post(route('admin.schedule.sessions.store'), $this->payload($client, $coach))
            ->assertSessionHasErrors('client_id');
    }

    public function test_end_time_must_be_after_start_time(): void
    {
        $admin = User::factory()->admin()->withProfile()->create();
        $coach = User::factory()->coach()->withProfile()->create();
        $client = $this->readyClient();

        $this->actingAs($admin)
            ->from(route('admin.schedule.sessions.create'))
            ->post(route('admin.schedule.sessions.store'), $this->payload($client, $coach, [
                'start_time' => '18:00',
                'end_time' => '18:00',
            ]))
            ->assertSessionHasErrors('end_time');
    }

    public function test_daily_schedule_lists_sessions_for_the_selected_date(): void
    {
        $admin = User::factory()->admin()->withProfile()->create();
        $coach = User::factory()->coach()->withProfile()->create();
        $client = $this->readyClient();
        $date = today()->addDay()->toDateString();

        $this->actingAs($admin)
            ->post(route('admin.schedule.sessions.store'), $this->payload($client, $coach, [
                'session_date' => $date,
            ]));

        $this->actingAs($admin)
            ->get(route('admin.schedule.index', ['date' => $date, 'view' => 'day']))
            ->assertOk()
            ->assertSee($client->full_name)
            ->assertSee('18:00')
            ->assertSee('Mark Done');
    }

    public function test_weekly_schedule_groups_sessions_and_preserves_filters(): void
    {
        $admin = User::factory()->admin()->withProfile()->create();
        $coach = User::factory()->coach()->withProfile()->create(['name' => 'Ahmad']);
        $client = $this->readyClient();
        $date = today()->startOfWeek(Carbon::MONDAY)->addDays(2)->toDateString();

        $this->actingAs($admin)
            ->post(route('admin.schedule.sessions.store'), $this->payload($client, $coach, [
                'session_date' => $date,
            ]));

        $this->actingAs($admin)
            ->get(route('admin.schedule.index', [
                'date' => $date,
                'view' => 'week',
                'coach_id' => $coach->id,
                'status' => 'pending',
            ]))
            ->assertOk()
            ->assertSee($client->full_name)
            ->assertSee('Ahmad')
            ->assertSee('Previous week')
            ->assertSee('Reset');
    }

    public function test_client_details_lists_session_history(): void
    {
        $admin = User::factory()->admin()->withProfile()->create();
        $coach = User::factory()->coach()->withProfile()->create(['name' => 'Ahmad']);
        $client = $this->readyClient();

        $this->actingAs($admin)
            ->post(route('admin.schedule.sessions.store'), $this->payload($client, $coach, [
                'notes' => 'Work the jab.',
            ]));

        $this->actingAs($admin)
            ->get(route('admin.clients.show', $client))
            ->assertOk()
            ->assertSee('Session history')
            ->assertSee('Ahmad')
            ->assertSee('Pending')
            ->assertSee('8 Sessions');
    }

    protected function readyClient(int $purchased = 8, int $used = 0): Client
    {
        $client = Client::factory()->create();
        $catalog = Package::factory()->create([
            'name' => $purchased.' Sessions',
            'sessions_count' => $purchased,
            'price' => '150.00',
        ]);

        ClientPackage::factory()->for($client)->forPackage($catalog)->create([
            'purchased_sessions' => $purchased,
            'used_sessions' => $used,
            'status' => $used >= $purchased ? ClientPackageStatus::Completed : ClientPackageStatus::Active,
        ]);

        return $client->fresh(['currentPackage']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function payload(Client $client, User $coach, array $overrides = []): array
    {
        return array_merge([
            'client_id' => $client->id,
            'coach_user_id' => $coach->id,
            'session_date' => today()->addDay()->toDateString(),
            'start_time' => '18:00',
            'end_time' => '19:00',
        ], $overrides);
    }
}
