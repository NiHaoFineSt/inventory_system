<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_asset_team_can_assign_unassigned_units(): void
    {
        $admin = User::factory()->assetTeam()->create();

        Asset::factory(2)->create(['asset_name' => 'Ergonomic Desk', 'category' => 'Furniture']);

        $response = $this->actingAs($admin)->postJson('/assets/assign', [
            'asset_name' => 'Ergonomic Desk',
            'category' => 'Furniture',
            'department' => 'Marketing',
            'employee' => 'Jane Doe',
            'quantity' => 2,
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseCount('assets', 2);
        $this->assertDatabaseMissing('assets', ['assigned_to' => null]);
    }

    public function test_assignment_fails_when_not_enough_units_are_available(): void
    {
        $admin = User::factory()->assetTeam()->create();

        Asset::factory(1)->create(['asset_name' => 'Ergonomic Desk', 'category' => 'Furniture']);

        $response = $this->actingAs($admin)->postJson('/assets/assign', [
            'asset_name' => 'Ergonomic Desk',
            'category' => 'Furniture',
            'department' => 'Marketing',
            'quantity' => 5,
        ]);

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_asset_team_can_manage_categories(): void
    {
        $admin = User::factory()->assetTeam()->create();

        $this->actingAs($admin)->postJson('/categories/store', ['category_name' => 'Electronics'])
            ->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('assets', ['category' => 'Electronics']);

        $this->actingAs($admin)->putJson('/categories/update', [
            'old_name' => 'Electronics',
            'new_name' => 'IT Hardware',
        ])->assertOk();

        $this->assertDatabaseHas('assets', ['category' => 'IT Hardware']);

        $this->actingAs($admin)->deleteJson('/categories/delete', ['category_name' => 'IT Hardware'])
            ->assertOk();

        $this->assertDatabaseMissing('assets', ['category' => 'IT Hardware']);
    }

    public function test_staff_cannot_access_the_asset_directory(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->get('/assets')->assertForbidden();
    }
}
