<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AuditLog;
use App\Models\DamageReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventorySystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_and_role_redirect(): void
    {
        $staff = User::factory()->create([
            'email' => 'staff@example.com',
            'password' => bcrypt('password'),
            'role' => 'staff',
        ]);

        $response = $this->post('/login', [
            'email' => 'staff@example.com',
            'password' => 'password',
            'role' => 'staff',
        ]);

        $response->assertRedirect('/staff_dashboard');
        $this->assertAuthenticatedAs($staff);
    }

    public function test_bulk_tag_generation_saves_assets_and_audit_log(): void
    {
        $admin = User::factory()->create([
            'role' => 'asset_team',
        ]);

        $response = $this->actingAs($admin)->postJson('/generate-tags', [
            'category' => 'Furniture',
            'asset_type' => 'Ergonomic Desk',
            'prefix' => 'DSK-',
            'start_num' => 1,
            'count' => 3,
            'location' => 'Head Office',
        ]);

        $response->assertOk();
        $response->assertJson(['status' => 'success']);

        $this->assertDatabaseCount('assets', 3);
        $this->assertDatabaseHas('assets', [
            'tracking_number' => 'TRK-DSK-001',
            'asset_name' => 'Ergonomic Desk',
            'condition' => 'Good',
        ]);

        $this->assertDatabaseCount('audit_logs', 1);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'TAG_GENERATED',
            'asset_name' => 'Ergonomic Desk',
        ]);
    }

    public function test_staff_can_submit_damage_report(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
        ]);

        $asset = Asset::create([
            'tracking_number' => 'TRK-MON-001',
            'asset_name' => 'Dell Monitor',
            'category' => 'IT Hardware',
            'prefix' => 'MON-',
            'unit_number' => 1,
            'location' => 'Head Office',
            'condition' => 'Good',
        ]);

        $response = $this->actingAs($staff)->post('/report_issue', [
            'tracking_number' => 'TRK-MON-001',
            'asset_name' => 'Dell Monitor',
            'issue_type' => 'Hardware Malfunction',
            'description' => 'Screen is blinking frequently.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('damage_reports', [
            'user_id' => $staff->id,
            'tracking_number' => 'TRK-MON-001',
            'asset_name' => 'Dell Monitor',
            'status' => 'Pending',
        ]);

        $this->assertEquals('Damaged', $asset->fresh()->condition);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $staff->id,
            'action' => 'ADDED',
            'asset_name' => 'Dell Monitor',
        ]);
    }

    public function test_damage_queue_status_update(): void
    {
        $admin = User::factory()->create([
            'role' => 'asset_team',
        ]);

        $report = DamageReport::create([
            'user_id' => $admin->id,
            'tracking_number' => 'TRK-MON-002',
            'asset_name' => 'Dell Monitor 2',
            'issue_type' => 'Physical Damage',
            'description' => 'Broken stand',
            'status' => 'Pending',
        ]);

        $response = $this->actingAs($admin)->postJson("/damage_queue/{$report->id}/status", [
            'status' => 'In Repair',
        ]);

        $response->assertOk();
        $response->assertJson(['status' => 'success']);

        $this->assertEquals('In Repair', $report->fresh()->status);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'EDITED',
            'asset_name' => 'Dell Monitor 2',
        ]);
    }

    public function test_damage_queue_and_log_history_views_render(): void
    {
        $admin = User::factory()->create([
            'role' => 'asset_team',
        ]);

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

        $queueResponse = $this->actingAs($admin)->get('/damage_queue');
        $queueResponse->assertOk();
        $queueResponse->assertSee('TRK-TST-001');

        $logResponse = $this->actingAs($admin)->get('/log_history');
        $logResponse->assertOk();
        $logResponse->assertSee('Test Asset');
    }
}
