<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AuditLog;
use App\Models\DamageReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_asset_analysis_page_renders_for_asset_team(): void
    {
        $admin = User::factory()->assetTeam()->create();
        Asset::factory(3)->create();

        $this->actingAs($admin)->get('/analysis/asset')->assertOk();
    }

    public function test_location_analysis_page_renders_for_asset_team(): void
    {
        $admin = User::factory()->assetTeam()->create();
        Asset::factory(3)->create(['location' => 'Head Office']);

        $this->actingAs($admin)->get('/analysis/location')->assertOk();
    }

    public function test_log_history_page_renders_for_asset_team(): void
    {
        $admin = User::factory()->assetTeam()->create();

        $this->actingAs($admin)->get('/logs')->assertOk();
    }

    public function test_damage_queue_and_log_history_views_render(): void
    {
        $admin = User::factory()->assetTeam()->create();

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'ADDED',
            'asset_name' => 'Test Asset',
            'description' => 'Created test asset',
        ]);

        DamageReport::create([
            'user_id' => $admin->id,
            'tracking_number' => 'TRK-TST-001',
            'asset_name' => 'Test Asset',
            'issue_type' => 'Physical Damage',
            'description' => 'Test description',
            'status' => 'Pending',
        ]);

        $this->actingAs($admin)->get('/damage-queue')
            ->assertOk()
            ->assertSee('TRK-TST-001');

        $this->actingAs($admin)->get('/logs')
            ->assertOk()
            ->assertSee('Test Asset');
    }
}
