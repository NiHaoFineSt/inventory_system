<?php

namespace App\Services;

use App\Enums\AssetCondition;
use App\Enums\AssetStatus;
use App\Enums\AuditAction;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AssetService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * Assign a quantity of unassigned units to a department/person.
     *
     * @return array{count: int, location: string}
     */
    public function assign(User $actor, array $data): array
    {
        return DB::transaction(function () use ($actor, $data) {
            $assets = Asset::where('asset_name', $data['asset_name'])
                ->where('category', $data['category'])
                ->where(function ($query) {
                    $query->whereNull('assigned_to')->orWhere('assigned_to', '');
                })
                ->lockForUpdate()
                ->take($data['quantity'])
                ->get();

            if ($assets->count() < $data['quantity']) {
                throw new RuntimeException('No unassigned units available for this asset type.');
            }

            $location = $data['department'];
            $assignedTo = $data['employee'] ?? null;

            foreach ($assets as $asset) {
                $asset->update([
                    'location' => $location,
                    'assigned_to' => $assignedTo,
                    'status' => AssetStatus::Active,
                ]);
            }

            $this->auditLogger->log(
                user: $actor,
                action: AuditAction::Assigned,
                description: "Assigned {$assets->count()} unit(s) of {$data['asset_name']} to {$location}".($assignedTo ? " ({$assignedTo})" : ''),
                assetName: $data['asset_name'],
            );

            return ['count' => $assets->count(), 'location' => $location];
        });
    }

    public function createCategory(User $actor, string $categoryName): Asset
    {
        $asset = Asset::create([
            'category' => $categoryName,
            'asset_name' => "General {$categoryName}",
            'tracking_number' => 'CAT-'.strtoupper(substr(md5(uniqid()), 0, 6)),
            'status' => AssetStatus::Inactive,
            'condition' => AssetCondition::Good,
            'location' => null,
        ]);

        $this->auditLogger->log(
            user: $actor,
            action: AuditAction::Added,
            description: "Added category {$categoryName}",
            assetName: $asset->asset_name,
        );

        return $asset;
    }

    public function renameCategory(User $actor, string $oldName, string $newName): int
    {
        $affected = Asset::where('category', $oldName)->update(['category' => $newName]);

        $this->auditLogger->log(
            user: $actor,
            action: AuditAction::Edited,
            description: "Renamed category {$oldName} to {$newName}",
        );

        return $affected;
    }

    public function deleteCategory(User $actor, string $categoryName): int
    {
        $affected = Asset::where('category', $categoryName)->delete();

        $this->auditLogger->log(
            user: $actor,
            action: AuditAction::Deleted,
            description: "Deleted category {$categoryName}",
        );

        return $affected;
    }

    public function updateAsset(User $actor, Asset $asset, array $data): Asset
    {
        $asset->update($data);

        $this->auditLogger->log(
            user: $actor,
            action: AuditAction::Edited,
            description: "Updated asset {$asset->tracking_number}",
            assetName: $asset->asset_name,
            trackingNumber: $asset->tracking_number,
        );

        return $asset->fresh();
    }

    public function deleteAsset(User $actor, Asset $asset): void
    {
        $this->auditLogger->log(
            user: $actor,
            action: AuditAction::Deleted,
            description: "Deleted asset {$asset->tracking_number}",
            assetName: $asset->asset_name,
            trackingNumber: $asset->tracking_number,
        );

        $asset->delete();
    }
}
