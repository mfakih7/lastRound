<?php

namespace Tests\Feature;

use App\Enums\ClientPackageStatus;
use App\Enums\TrainingSessionStatus;
use App\Models\Client;
use App\Models\ClientPackage;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainingSessionAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_coach_cannot_manage_sessions_or_admin_schedule(): void
    {
        $coach = User::factory()->coach()->withProfile()->create();
        $other = User::factory()->coach()->withProfile()->create();
        $client = Client::factory()->create();
        ClientPackage::factory()->for($client)->create(['status' => ClientPackageStatus::Active]);
        $session = TrainingSession::factory()->create([
            'coach_user_id' => $other->id,
            'client_id' => $client->id,
        ]);

        $this->actingAs($coach)->get(route('admin.schedule.index'))->assertForbidden();
        $this->actingAs($coach)->get(route('admin.schedule.sessions.create'))->assertForbidden();
        $this->actingAs($coach)->post(route('admin.schedule.sessions.store'), [
            'client_id' => $client->id,
            'coach_user_id' => $coach->id,
            'session_date' => today()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
        ])->assertForbidden();
        $this->actingAs($coach)->get(route('admin.schedule.sessions.edit', $session))->assertForbidden();
        $this->actingAs($coach)->put(route('admin.schedule.sessions.update', $session), [
            'client_id' => $client->id,
            'coach_user_id' => $other->id,
            'session_date' => today()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'status' => TrainingSessionStatus::Done->value,
        ])->assertForbidden();
        $this->actingAs($coach)->post(route('admin.schedule.sessions.done', $session))->assertForbidden();
        $this->actingAs($coach)->post(route('admin.schedule.sessions.cancel', $session))->assertForbidden();
        $this->actingAs($coach)->delete(route('admin.schedule.sessions.destroy', $session))->assertForbidden();
    }

    public function test_coach_portal_shows_only_own_sessions_and_no_status_actions(): void
    {
        $coachA = User::factory()->coach()->withProfile()->create(['name' => 'Ahmad']);
        $coachB = User::factory()->coach()->withProfile()->create(['name' => 'Ali']);
        $clientA = Client::factory()->create(['full_name' => 'John Smith']);
        $clientB = Client::factory()->create(['full_name' => 'Karim Hassan']);

        TrainingSession::factory()->create([
            'coach_user_id' => $coachA->id,
            'client_id' => $clientA->id,
            'session_date' => today()->toDateString(),
            'start_time' => '18:00:00',
            'end_time' => '19:00:00',
            'notes' => 'Keep the guard high.',
        ]);
        TrainingSession::factory()->create([
            'coach_user_id' => $coachB->id,
            'client_id' => $clientB->id,
            'session_date' => today()->toDateString(),
            'start_time' => '18:00:00',
            'end_time' => '19:00:00',
        ]);

        $this->actingAs($coachA)
            ->get(route('coach.schedule.index'))
            ->assertOk()
            ->assertSee('John Smith')
            ->assertSee('Keep the guard high.')
            ->assertDontSee('Karim Hassan')
            ->assertDontSee('Mark Done')
            ->assertDontSee('Schedule session');
    }

    public function test_admin_can_open_schedule_and_dashboard_today_section(): void
    {
        $admin = User::factory()->admin()->withProfile()->create();

        $this->actingAs($admin)
            ->get(route('admin.schedule.index'))
            ->assertOk()
            ->assertSee('Schedule session');

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Today’s schedule')
            ->assertSee('Pending Today');
    }
}
