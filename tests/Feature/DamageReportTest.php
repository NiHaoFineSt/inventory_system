<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\DamageReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DamageReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_submit_a_damage_report_with_a_photo(): void
    {
        Storage::fake('public');

        $staff = User::factory()->staff()->create();

        Asset::create([
            'tracking_number' => 'TRK-MON-001',
            'asset_name' => 'Dell Monitor',
            'category' => 'IT Hardware',
            'condition' => 'Good',
            'status' => 'Active',
        ]);

        $response = $this->actingAs($staff)->post('/damage-queue/report', [
            'tracking_number' => 'TRK-MON-001',
            'asset_name' => 'Dell Monitor',
            'issue_type' => 'Hardware Malfunction',
            'description' => 'Screen is blinking frequently.',
            'image' => UploadedFile::fake()->image('damage.jpg'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('damage_reports', [
            'user_id' => $staff->id,
            'tracking_number' => 'TRK-MON-001',
            'status' => 'Pending',
        ]);

        $report = DamageReport::first();
        Storage::disk('public')->assertExists($report->image_path);

        $this->assertSame('Damaged', Asset::where('tracking_number', 'TRK-MON-001')->first()->condition->value);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $staff->id,
            'action' => 'REPORTED',
            'tracking_number' => 'TRK-MON-001',
        ]);
    }

    public function test_asset_team_can_resolve_a_damage_report(): void
    {
        $admin = User::factory()->assetTeam()->create();

        $report = DamageReport::create([
            'user_id' => $admin->id,
            'reported_by' => $admin->name,
            'tracking_number' => 'TRK-MON-002',
            'asset_name' => 'Dell Monitor 2',
            'issue_type' => 'Physical Damage',
            'description' => 'Broken stand',
            'status' => 'Pending',
        ]);

        $response = $this->actingAs($admin)->patchJson("/damage-queue/{$report->id}/status", [
            'status' => 'Resolved',
        ]);

        $response->assertOk();
        $this->assertSame('Resolved', $report->fresh()->status->value);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'STATUS_CHANGED',
            'tracking_number' => 'TRK-MON-002',
        ]);
    }

    public function test_staff_cannot_resolve_a_damage_report(): void
    {
        $staff = User::factory()->staff()->create();

        $report = DamageReport::create([
            'user_id' => $staff->id,
            'tracking_number' => 'TRK-MON-003',
            'asset_name' => 'Dell Monitor 3',
            'issue_type' => 'Physical Damage',
            'description' => 'Cracked case',
            'status' => 'Pending',
        ]);

        $this->actingAs($staff)
            ->patchJson("/damage-queue/{$report->id}/status", ['status' => 'Resolved'])
            ->assertForbidden();
    }
}
