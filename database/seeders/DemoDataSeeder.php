<?php

namespace Database\Seeders;

use App\Enums\ClientStatus;
use App\Exceptions\SchedulingException;
use App\Models\Client;
use App\Models\Package;
use App\Models\User;
use App\Services\ClientPackageService;
use App\Services\TrainingSessionService;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('username', 'admin')->first();
        $marcus = User::query()->where('username', 'marcus')->first();
        $sofia = User::query()->where('username', 'sofia')->first();
        $eight = Package::query()->where('name', '8 Sessions')->first();
        $twelve = Package::query()->where('name', '12 Sessions')->first();

        if (! $admin || ! $marcus || ! $sofia || ! $eight || ! $twelve) {
            return;
        }

        $packages = app(ClientPackageService::class);
        $sessions = app(TrainingSessionService::class);

        $john = $this->client('John Smith', '+961 70 100 001', $marcus->id);
        $jad = $this->client('Jad Nasser', '+961 70 100 002', $marcus->id);
        $karim = $this->client('Karim Haddad', '+961 70 100 003', $sofia->id);
        $lina = $this->client('Lina Farah', '+961 70 100 004', $sofia->id);
        $noura = $this->client('Noura Saleh', '+961 70 100 005', $admin->id);
        $this->client('Rami Khoury', '+961 70 100 006', null);
        $this->client('Maya Chehab', '+961 70 100 007', $marcus->id, ClientStatus::Inactive);
        $samir = $this->client('Samir Abbas', '+961 70 100 008', $sofia->id);

        $this->assignIfNeeded($packages, $john, $eight, 8, '150.00');
        $this->assignIfNeeded($packages, $jad, $twelve, 12, '250.00');
        $this->assignIfNeeded($packages, $karim, $eight, 8, '150.00');
        $this->assignIfNeeded($packages, $lina, $eight, 8, '150.00');
        $this->assignIfNeeded($packages, $noura, $eight, 8, '150.00');
        $this->assignIfNeeded($packages, $samir, $eight, 8, '150.00');

        $this->schedule($sessions, $john, $marcus, today()->toDateString(), '09:00', '10:00', 'Footwork and guard.');
        $this->schedule($sessions, $jad, $sofia, today()->toDateString(), '11:00', '12:00');
        $this->schedule($sessions, $noura, $admin, today()->toDateString(), '18:00', '19:00', 'Pads with the Head Coach.');
        $this->schedule($sessions, $lina, $marcus, today()->addDay()->toDateString(), '18:00', '19:00');
        $this->schedule($sessions, $jad, $sofia, today()->addDays(2)->toDateString(), '10:00', '11:00');

        $done = $this->schedule($sessions, $samir, $marcus, today()->subDay()->toDateString(), '17:00', '18:00');
        if ($done?->isPending()) {
            $sessions->markDone($done);
        }

        $cancelled = $this->schedule($sessions, $jad, $marcus, today()->addDay()->toDateString(), '09:00', '10:00');
        if ($cancelled?->isPending()) {
            $sessions->cancel($cancelled);
        }

        $this->completePastSessions($sessions, $john, $marcus, 6);
        $this->completePastSessions($sessions, $karim, $sofia, 8);
    }

    protected function client(string $name, string $phone, ?int $coachId, ClientStatus $status = ClientStatus::Active): Client
    {
        return Client::query()->firstOrCreate(
            ['phone' => $phone],
            [
                'full_name' => $name,
                'preferred_coach_id' => $coachId,
                'status' => $status,
            ],
        );
    }

    protected function assignIfNeeded(ClientPackageService $packages, Client $client, Package $package, int $sessions, string $price): void
    {
        $client->load('currentPackage');

        if ($client->currentPackage !== null) {
            return;
        }

        $packages->assign($client, [
            'package_id' => $package->id,
            'purchased_sessions' => $sessions,
            'price_paid' => $price,
            'starts_at' => today()->subWeeks(2)->toDateString(),
        ]);
    }

    protected function schedule(
        TrainingSessionService $sessions,
        Client $client,
        User $coach,
        string $date,
        string $start,
        string $end,
        ?string $notes = null,
    ) {
        $exists = $client->trainingSessions()
            ->whereDate('session_date', $date)
            ->where('start_time', 'like', $start.'%')
            ->exists();

        if ($exists) {
            return $client->trainingSessions()
                ->whereDate('session_date', $date)
                ->where('start_time', 'like', $start.'%')
                ->first();
        }

        try {
            return $sessions->create([
                'client_id' => $client->id,
                'coach_user_id' => $coach->id,
                'session_date' => $date,
                'start_time' => $start,
                'end_time' => $end,
                'notes' => $notes,
            ]);
        } catch (SchedulingException) {
            return null;
        }
    }

    protected function completePastSessions(TrainingSessionService $sessions, Client $client, User $coach, int $count): void
    {
        $client->load('currentPackage');
        $alreadyUsed = (int) $client->currentPackage?->used_sessions;

        for ($offset = 3; $alreadyUsed < $count; $offset++) {
            $session = $this->schedule(
                $sessions,
                $client,
                $coach,
                today()->subDays($offset)->toDateString(),
                '16:00',
                '17:00',
            );

            if ($session === null) {
                break;
            }

            if ($session->isPending()) {
                $sessions->markDone($session);
                $alreadyUsed++;
            } elseif ($session->isDone()) {
                $alreadyUsed++;
            }
        }
    }
}
