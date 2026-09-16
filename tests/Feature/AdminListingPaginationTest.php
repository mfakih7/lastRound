<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminListingPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_listing_paginates_server_side_and_preserves_query(): void
    {
        $admin = User::factory()->admin()->create();
        Client::factory()->count(25)->create(['status' => 'active']);
        Client::factory()->create(['full_name' => 'Ziad Unique', 'status' => 'active']);

        $this->actingAs($admin)
            ->get(route('admin.clients.index', [
                'search' => 'Ziad Unique',
                'status' => 'active',
                'sort' => 'full_name',
                'direction' => 'asc',
                'per_page' => 10,
            ]))
            ->assertOk()
            ->assertSee('Ziad Unique')
            ->assertSee('name="per_page"', false);

        $pageTwo = $this->actingAs($admin)
            ->get(route('admin.clients.index', [
                'status' => 'active',
                'sort' => 'full_name',
                'direction' => 'asc',
                'per_page' => 10,
                'page' => 2,
            ]));

        $pageTwo->assertOk()
            ->assertSee('per_page=10', false)
            ->assertSee('status=active', false)
            ->assertViewHas('clients', function ($clients) {
                return $clients->currentPage() === 2
                    && $clients->perPage() === 10
                    && $clients->total() >= 26;
            });
    }
}
