<?php

namespace App\Services;

use App\Enums\AssetCondition;
use App\Enums\AuditAction;
use App\Enums\DamageReportStatus;
use App\Models\Asset;
use App\Models\DamageReport;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class DamageReportService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function submit(User $reporter, array $data, ?UploadedFile $photo): DamageReport
    {
        return DB::transaction(function () use ($reporter, $data, $photo) {
            $imagePath = $photo?->store('damage_photos', 'public');

            $report = DamageReport::create([
                'user_id' => $reporter->id,
                'reported_by' => $reporter->name ?? $reporter->email,
                'tracking_number' => $data['tracking_number'],
                'asset_name' => $data['asset_name'],
                'issue_type' => $data['issue_type'],
                'description' => $data['description'],
                'image_path' => $imagePath,
                'status' => DamageReportStatus::Pending,
            ]);

            Asset::where('tracking_number', $data['tracking_number'])
                ->update(['condition' => AssetCondition::Damaged]);

            $this->auditLogger->log(
                user: $reporter,
                action: AuditAction::Reported,
                description: "Issue ({$data['issue_type']}): {$data['description']}",
                assetName: $data['asset_name'],
                trackingNumber: $data['tracking_number'],
            );

            return $report;
        });
    }

    public function updateStatus(User $actor, DamageReport $report, DamageReportStatus $status): DamageReport
    {
        $report->update(['status' => $status]);

        $this->auditLogger->log(
            user: $actor,
            action: AuditAction::StatusChanged,
            description: "Damage report for {$report->tracking_number} set to {$status->value}",
            assetName: $report->asset_name,
            trackingNumber: $report->tracking_number,
        );

        return $report->fresh();
    }
}
