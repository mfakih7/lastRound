<?php

namespace Tests\Feature;

use App\Enums\ClientStatus;
use App\Enums\SessionBalanceStatus;
use App\Models\Client;
use App\Models\ClientPackage;
use App\Models\CoachProfile;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_clients(): void
    {
        $admin = User::factory()->admin()->create();
        Client::factory()->create(['full_name' => 'John Smith']);

        $this->actingAs($admin)
            ->get(route('admin.clients.index'))
            ->assertOk()
            ->assertSee('John Smith');
    }

    public function test_admin_can_create_client(): void
    {
        $admin = User::factory()->admin()->create();
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->create(['user_id' => $coach->id]);

        $response = $this->actingAs($admin)->post(route('admin.clients.store'), [
            'full_name' => 'Karim Haddad',
            'phone' => '+961 70 000 001',
            'email' => 'karim@example.test',
            'preferred_coach_id' => $coach->id,
            'status' => ClientStatus::Active->value,
        ]);

        $client = Client::query()->where('full_name', 'Karim Haddad')->first();

        $this->assertNotNull($client);
        $this->assertSame(SessionBalanceStatus::NoPackage, $client->sessionBalanceStatus());
        $response->assertRedirect(route('admin.clients.show', $client));
    }

    public function test_validation_rejects_invalid_client(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('admin.clients.create'))
            ->post(route('admin.clients.store'), [
                'full_name' => '',
                'phone' => '',
                'status' => 'nope',
            ])
            ->assertRedirect(route('admin.clients.create'))
            ->assertSessionHasErrors(['full_name', 'phone', 'status']);
    }

    public function test_admin_can_edit_client(): void
    {
        $admin = User::factory()->admin()->create();
        $client = Client::factory()->create(['full_name' => 'Old Name']);

        $this->actingAs($admin)
            ->put(route('admin.clients.update', $client), [
                'full_name' => 'New Name',
                'phone' => $client->phone,
                'status' => ClientStatus::Active->value,
            ])
            ->assertRedirect(route('admin.clients.show', $client));

        $this->assertSame('New Name', $client->fresh()->full_name);
    }

    public function test_admin_can_activate_and_deactivate_client(): void
    {
        $admin = User::factory()->admin()->create();
        $client = Client::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.clients.deactivate', $client))
            ->assertRedirect();

        $this->assertSame(ClientStatus::Inactive, $client->fresh()->status);

        $this->actingAs($admin)
            ->post(route('admin.clients.activate', $client))
            ->assertRedirect();

        $this->assertSame(ClientStatus::Active, $client->fresh()->status);
    }

    public function test_client_search_works(): void
    {
        $admin = User::factory()->admin()->create();
        Client::factory()->create(['full_name' => 'Mohamad Ali', 'phone' => '111']);
        Client::factory()->create(['full_name' => 'Jad Khoury', 'phone' => '222']);

        $this->actingAs($admin)
            ->get(route('admin.clients.index', ['search' => 'mohamad']))
            ->assertOk()
            ->assertSee('Mohamad Ali')
            ->assertDontSee('Jad Khoury');
    }

    public function test_client_filters_work(): void
    {
        $admin = User::factory()->admin()->create();
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->create(['user_id' => $coach->id]);
        $package = Package::factory()->create(['name' => '12 Sessions']);

        $matching = Client::factory()->forCoach($coach)->create(['full_name' => 'Filtered Client']);
        ClientPackage::factory()->for($matching)->forPackage($package)->create();
        Client::factory()->inactive()->create(['full_name' => 'Inactive Person']);

        $this->actingAs($admin)
            ->get(route('admin.clients.index', [
                'status' => 'active',
                'preferred_coach_id' => $coach->id,
                'package_id' => $package->id,
                'balance' => 'healthy',
            ]))
            ->assertOk()
            ->assertSee('Filtered Client')
            ->assertDontSee('Inactive Person');
    }

    public function test_client_pagination_works(): void
    {
        $admin = User::factory()->admin()->create();
        $oldest = Client::factory()->create(['full_name' => 'Oldest Client']);
        Client::factory()->count(20)->create();

        $this->actingAs($admin)
            ->get(route('admin.clients.index'))
            ->assertOk()
            ->assertDontSee('Oldest Client')
            ->assertSee('page=2');

        $this->actingAs($admin)
            ->get(route('admin.clients.index', ['page' => 2]))
            ->assertOk()
            ->assertSee('Oldest Client');

        $this->assertTrue($oldest->is($oldest));
    }

    public function test_client_with_history_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $client = Client::factory()->create();
        ClientPackage::factory()->for($client)->create();

        $this->actingAs($admin)
            ->from(route('admin.clients.show', $client))
            ->delete(route('admin.clients.destroy', $client))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('clients', ['id' => $client->id]);
    }

    public function test_coach_cannot_access_client_admin_routes(): void
    {
        $coach = User::factory()->coach()->create();
        $client = Client::factory()->create();

        $this->actingAs($coach)->get(route('admin.clients.index'))->assertForbidden();
        $this->actingAs($coach)->get(route('admin.clients.create'))->assertForbidden();
        $this->actingAs($coach)->post(route('admin.clients.store'), [])->assertForbidden();
        $this->actingAs($coach)->get(route('admin.clients.show', $client))->assertForbidden();
        $this->actingAs($coach)->get(route('admin.clients.edit', $client))->assertForbidden();
        $this->actingAs($coach)->put(route('admin.clients.update', $client), [])->assertForbidden();
        $this->actingAs($coach)->post(route('admin.clients.deactivate', $client))->assertForbidden();
        $this->actingAs($coach)->get(route('admin.clients.packages.create', $client))->assertForbidden();
    }
}
