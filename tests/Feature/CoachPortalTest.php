<?php

namespace Tests\Feature;

use App\Enums\TrainingSessionStatus;
use App\Models\Client;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CoachPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-09-09 10:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_coach_sees_only_their_own_sessions(): void
    {
        [$coachA, $coachB, $clientA, $clientB] = $this->twoCoachesWithSessions();

        $this->actingAs($coachA)
            ->get(route('coach.schedule.index'))
            ->assertOk()
            ->assertSee('Welcome, '.$coachA->name)
            ->assertSee($clientA->full_name)
            ->assertDontSee($clientB->full_name);

        $this->actingAs($coachB)
            ->get(route('coach.schedule.index'))
            ->assertOk()
            ->assertSee($clientB->full_name)
            ->assertDontSee($clientA->full_name);
    }

    public function test_coach_cannot_use_query_parameters_to_view_another_coach_schedule(): void
    {
        [$coachA, $coachB, $clientA, $clientB] = $this->twoCoachesWithSessions();

        $this->actingAs($coachA)
            ->get(route('coach.schedule.index', [
                'coach_id' => $coachB->id,
                'coach' => $coachB->id,
            ]))
            ->assertOk()
            ->assertSee($clientA->full_name)
            ->assertDontSee($clientB->full_name);

        $this->actingAs($coachA)
            ->get(route('admin.coaches.schedule', $coachB))
            ->assertForbidden();
    }

    public function test_today_tomorrow_and_week_filters_work(): void
    {
        $coach = User::factory()->coach()->withProfile()->create(['name' => 'Ahmad']);
        $todayClient = Client::factory()->create(['full_name' => 'Today Client']);
        $tomorrowClient = Client::factory()->create(['full_name' => 'Tomorrow Client']);
        $weekClient = Client::factory()->create(['full_name' => 'Friday Client']);
        $nextWeekClient = Client::factory()->create(['full_name' => 'Next Week Client']);

        TrainingSession::factory()->create([
            'coach_user_id' => $coach->id,
            'client_id' => $todayClient->id,
            'session_date' => '2026-09-09',
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
        ]);
        TrainingSession::factory()->create([
            'coach_user_id' => $coach->id,
            'client_id' => $tomorrowClient->id,
            'session_date' => '2026-09-10',
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
        ]);
        TrainingSession::factory()->create([
            'coach_user_id' => $coach->id,
            'client_id' => $weekClient->id,
            'session_date' => '2026-09-11',
            'start_time' => '18:30:00',
            'end_time' => '19:30:00',
        ]);
        TrainingSession::factory()->create([
            'coach_user_id' => $coach->id,
            'client_id' => $nextWeekClient->id,
            'session_date' => '2026-09-14',
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
        ]);

        $this->actingAs($coach)
            ->get(route('coach.schedule.index'))
            ->assertOk()
            ->assertSee('Today Client')
            ->assertDontSee('Tomorrow Client')
            ->assertDontSee('Friday Client')
            ->assertDontSee('Next Week Client');

        $this->actingAs($coach)
            ->get(route('coach.schedule.index', ['period' => 'tomorrow']))
            ->assertOk()
            ->assertSee('Tomorrow Client')
            ->assertDontSee('Today Client')
            ->assertDontSee('Friday Client');

        $this->actingAs($coach)
            ->get(route('coach.schedule.index', ['period' => 'week']))
            ->assertOk()
            ->assertSee('Today Client')
            ->assertSee('Tomorrow Client')
            ->assertSee('Friday Client')
            ->assertDontSee('Next Week Client');
    }

    public function test_coach_cannot_change_session_state(): void
    {
        [$coachA] = $this->twoCoachesWithSessions();
        $session = TrainingSession::query()->where('coach_user_id', $coachA->id)->first();

        $this->actingAs($coachA)
            ->patch('/coach/schedule/'.$session->id, [
                'status' => TrainingSessionStatus::Done->value,
            ])
            ->assertNotFound();

        $this->actingAs($coachA)
            ->put('/coach/sessions/'.$session->id, [
                'status' => TrainingSessionStatus::Done->value,
            ])
            ->assertNotFound();

        $this->actingAs($coachA)
            ->post('/coach/sessions', [
                'client_id' => $session->client_id,
                'session_date' => today()->toDateString(),
            ])
            ->assertNotFound();

        $this->assertSame(TrainingSessionStatus::Pending, $session->fresh()->status);
    }

    public function test_coach_portal_does_not_expose_admin_navigation_or_client_directory(): void
    {
        $coach = User::factory()->coach()->withProfile()->create();

        $this->actingAs($coach)
            ->get(route('coach.schedule.index'))
            ->assertOk()
            ->assertSee('My Schedule')
            ->assertSee('My Profile')
            ->assertDontSee('href="'.route('admin.clients.index').'"', false)
            ->assertDontSee('href="'.route('admin.coaches.index').'"', false)
            ->assertDontSee('href="'.route('admin.packages.index').'"', false)
            ->assertDontSee('href="'.route('admin.settings.index').'"', false)
            ->assertDontSee('href="'.route('admin.dashboard').'"', false);

        $this->actingAs($coach)
            ->get(route('admin.clients.index'))
            ->assertForbidden();

        $this->actingAs($coach)
            ->get(route('admin.packages.index'))
            ->assertForbidden();

        $this->actingAs($coach)
            ->get(route('admin.settings.index'))
            ->assertForbidden();

        $this->actingAs($coach)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_coach_can_view_profile_and_change_own_password(): void
    {
        $coach = User::factory()->coach()->withProfile()->create([
            'name' => 'Ahmad Hassan',
            'username' => 'ahmad',
            'password' => 'password',
        ]);

        $this->actingAs($coach)
            ->get(route('coach.profile.show'))
            ->assertOk()
            ->assertSee('Ahmad Hassan')
            ->assertSee('ahmad');

        $this->actingAs($coach)
            ->from(route('coach.profile.show'))
            ->put(route('coach.profile.password.update'), [
                'current_password' => 'password',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertRedirect(route('coach.profile.show'))
            ->assertSessionHas('success', 'Password updated successfully.');

        $this->assertTrue(Hash::check('new-password-123', $coach->fresh()->password));
    }

    public function test_coach_password_change_requires_current_password(): void
    {
        $coach = User::factory()->coach()->withProfile()->create(['password' => 'password']);

        $this->actingAs($coach)
            ->from(route('coach.profile.show'))
            ->put(route('coach.profile.password.update'), [
                'current_password' => 'wrong-password',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertRedirect(route('coach.profile.show'))
            ->assertSessionHasErrors('current_password');
    }

    /**
     * @return array{0: User, 1: User, 2: Client, 3: Client}
     */
    protected function twoCoachesWithSessions(): array
    {
        $coachA = User::factory()->coach()->withProfile()->create(['name' => 'Ahmad']);
        $coachB = User::factory()->coach()->withProfile()->create(['name' => 'Ali']);
        $clientA = Client::factory()->create(['full_name' => 'John Smith']);
        $clientB = Client::factory()->create(['full_name' => 'Karim Hassan']);

        TrainingSession::factory()->create([
            'coach_user_id' => $coachA->id,
            'client_id' => $clientA->id,
            'session_date' => today()->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'notes' => 'Focus on jabs.',
        ]);

        TrainingSession::factory()->create([
            'coach_user_id' => $coachB->id,
            'client_id' => $clientB->id,
            'session_date' => today()->toDateString(),
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
        ]);

        return [$coachA, $coachB, $clientA, $clientB];
    }
}
