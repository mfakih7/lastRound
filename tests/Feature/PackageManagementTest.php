<?php

namespace Tests\Feature;

use App\Models\ClientPackage;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_package(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.packages.store'), [
            'name' => '12 Sessions',
            'sessions_count' => 12,
            'price' => '250.00',
            'description' => 'Standard 12-session training package.',
            'is_active' => '1',
        ]);

        $package = Package::query()->where('name', '12 Sessions')->first();

        $this->assertNotNull($package);
        $this->assertSame(12, $package->sessions_count);
        $this->assertSame('250.00', $package->price);
        $response->assertRedirect(route('admin.packages.show', $package));
    }

    public function test_invalid_package_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('admin.packages.create'))
            ->post(route('admin.packages.store'), [
                'name' => '',
                'sessions_count' => 0,
                'price' => '-10',
            ])
            ->assertRedirect(route('admin.packages.create'))
            ->assertSessionHasErrors(['name', 'sessions_count', 'price']);
    }

    public function test_admin_can_edit_package(): void
    {
        $admin = User::factory()->admin()->create();
        $package = Package::factory()->create([
            'name' => '12 Sessions',
            'price' => '250.00',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.packages.update', $package), [
                'name' => '12 Sessions',
                'sessions_count' => 12,
                'price' => '270.00',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.packages.show', $package));

        $this->assertSame('270.00', $package->fresh()->price);
    }

    public function test_package_with_purchase_history_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $package = Package::factory()->create();
        ClientPackage::factory()->forPackage($package)->create();

        $this->actingAs($admin)
            ->from(route('admin.packages.show', $package))
            ->delete(route('admin.packages.destroy', $package))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('packages', ['id' => $package->id]);
    }

    public function test_unused_package_can_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $package = Package::factory()->create();

        $this->actingAs($admin)
            ->delete(route('admin.packages.destroy', $package))
            ->assertRedirect(route('admin.packages.index'));

        $this->assertDatabaseMissing('packages', ['id' => $package->id]);
    }

    public function test_coach_cannot_access_package_routes(): void
    {
        $coach = User::factory()->coach()->create();
        $package = Package::factory()->create();

        $this->actingAs($coach)->get(route('admin.packages.index'))->assertForbidden();
        $this->actingAs($coach)->get(route('admin.packages.create'))->assertForbidden();
        $this->actingAs($coach)->post(route('admin.packages.store'), [])->assertForbidden();
        $this->actingAs($coach)->get(route('admin.packages.edit', $package))->assertForbidden();
        $this->actingAs($coach)->put(route('admin.packages.update', $package), [])->assertForbidden();
        $this->actingAs($coach)->delete(route('admin.packages.destroy', $package))->assertForbidden();
    }

    public function test_admin_can_search_and_filter_packages(): void
    {
        $admin = User::factory()->admin()->create();
        Package::factory()->create(['name' => '8 Sessions', 'is_active' => true]);
        Package::factory()->create(['name' => 'Private Hour', 'is_active' => false]);

        $this->actingAs($admin)
            ->get(route('admin.packages.index', ['search' => '8 sessions', 'status' => 'active']))
            ->assertOk()
            ->assertSee('8 Sessions')
            ->assertDontSee('Private Hour');
    }
}
