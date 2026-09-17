<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InventorySystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_damage_reporting_updates_asset_and_shows_on_dashboard_and_damage_queue(): void
    {
        $user = User::factory()->create([
            'name' => 'Aussy',
            'role' => 'staff',
        ]);

        $asset = Asset::create([
            'tracking_number' => 'DSK-021',
            'asset_name' => 'Adjustable Standing Desk',
            'category' => 'Furniture',
            'location' => 'Head Office',
            'condition' => 'Good',
            'status' => 'Available',
        ]);

        // Submit damage report
        $response = $this->actingAs($user)->post(route('report_damage.store'), [
            'tracking_number' => 'DSK-021',
            'asset_name' => 'Adjustable Standing Desk',
            'issue_type' => 'Missing Parts',
            'description' => 'The screw is missing',
        ]);

        $response->assertRedirect();

        // 1. Check damage report was created with status 'Pending Inspection'
        $this->assertDatabaseHas('damage_reports', [
            'tracking_number' => 'DSK-021',
            'status' => 'Pending Inspection',
        ]);

        // 2. Check asset table was updated to condition 'Damaged' and status 'Pending Repair'
        $this->assertDatabaseHas('assets', [
            'tracking_number' => 'DSK-021',
            'condition' => 'Damaged',
            'status' => 'Pending Repair',
        ]);

        // 3. Check Damage Queue route displays the pending ticket
        $admin = User::factory()->create(['role' => 'asset_team']);
        $queueResponse = $this->actingAs($admin)->get(route('damage_queue'));
        $queueResponse->assertOk();
        $queueResponse->assertSee('DSK-021');
        $queueResponse->assertSee('Pending Inspection');

        // 4. Check Asset Dashboard route attaches the damage data
        $dashboardResponse = $this->actingAs($admin)->get(route('asset_dashboard'));
        $dashboardResponse->assertOk();
        $dashboardResponse->assertSee('DSK-021');

        $viewAssets = $dashboardResponse->viewData('assets');
        $damagedAsset = $viewAssets->firstWhere('tracking_number', 'DSK-021');
        $this->assertNotNull($damagedAsset);
        $this->assertEquals('Damaged', $damagedAsset->condition);
        $this->assertEquals('Pending Inspection', $damagedAsset->latest_damage_status);

        // 5. Resolve the damage report
        $reportId = DB::table('damage_reports')->where('tracking_number', 'DSK-021')->value('id');
        $resolveResponse = $this->actingAs($admin)->patch(route('damage_queue.resolve', $reportId));
        $resolveResponse->assertRedirect();

        // 6. Check report is resolved and asset is restored
        $this->assertDatabaseHas('damage_reports', [
            'id' => $reportId,
            'status' => 'Resolved',
        ]);
        $this->assertDatabaseHas('assets', [
            'tracking_number' => 'DSK-021',
            'condition' => 'Good',
            'status' => 'Active',
        ]);
    }
}
