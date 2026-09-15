<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_asset_team_can_fetch_the_next_tracking_number(): void
    {
        $admin = User::factory()->assetTeam()->create();

        Asset::create([
            'tracking_number' => 'DSK-001',
            'asset_name' => 'Standing Desk',
            'category' => 'Furniture',
        ]);

        $response = $this->actingAs($admin)->getJson('/tags/next-number?prefix=DSK-');

        $response->assertOk()->assertJson(['success' => true, 'next_number' => 2]);
    }

    public function test_asset_team_can_bulk_generate_tags(): void
    {
        $admin = User::factory()->assetTeam()->create();

        $response = $this->actingAs($admin)->postJson('/tags/bulk-generate', [
            'category' => 'Furniture',
            'asset_name' => 'Ergonomic Desk',
            'prefix' => 'DSK-',
            'starting_number' => 1,
            'quantity' => 3,
            'location' => 'Head Office',
        ]);

        $response->assertCreated();
        $response->assertJson(['status' => 'success']);

        $this->assertDatabaseCount('assets', 3);
        $this->assertDatabaseHas('assets', [
            'tracking_number' => 'DSK-001',
            'asset_name' => 'Ergonomic Desk',
            'condition' => 'Good',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'TAG_GENERATED',
            'asset_name' => 'Ergonomic Desk',
        ]);
    }

    public function test_staff_cannot_bulk_generate_tags(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->postJson('/tags/bulk-generate', [
            'category' => 'Furniture',
            'asset_name' => 'Ergonomic Desk',
            'prefix' => 'DSK-',
            'starting_number' => 1,
            'quantity' => 1,
        ])->assertForbidden();
    }
}
