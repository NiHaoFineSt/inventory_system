<?php

namespace App\Services;

use App\Enums\AssetCondition;
use App\Enums\AssetStatus;
use App\Enums\AuditAction;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TagGeneratorService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function nextNumber(string $prefix): int
    {
        $prefix = trim($prefix);

        if ($prefix === '') {
            return 1;
        }

        $maxNum = 0;

        Asset::where('tracking_number', 'LIKE', $prefix.'%')
            ->pluck('tracking_number')
            ->each(function (string $tracking) use (&$maxNum) {
                if (preg_match('/(\d+)$/', $tracking, $matches)) {
                    $maxNum = max($maxNum, (int) $matches[1]);
                }
            });

        return $maxNum + 1;
    }

    /**
     * @return Collection<int, Asset>
     */
    public function storeBulk(User $actor, array $data): Collection
    {
        return DB::transaction(function () use ($actor, $data) {
            $prefix = strtoupper(trim($data['prefix']));
            $quantity = (int) $data['quantity'];
            $location = $data['location'] ?? null;
            $currentNum = (int) $data['starting_number'];

            $created = collect();

            for ($i = 0; $i < $quantity; $i++) {
                $trackingNumber = $prefix.str_pad((string) $currentNum, 3, '0', STR_PAD_LEFT);

                $created->push(Asset::create([
                    'category' => $data['category'],
                    'asset_name' => $data['asset_name'],
                    'tracking_number' => $trackingNumber,
                    'unit_number' => str_pad((string) $currentNum, 3, '0', STR_PAD_LEFT),
                    'prefix' => $prefix,
                    'qr_code_payload' => $trackingNumber,
                    'status' => AssetStatus::Active,
                    'condition' => AssetCondition::Good,
                    'location' => $location,
                ]));

                $currentNum++;
            }

            $this->auditLogger->log(
                user: $actor,
                action: AuditAction::TagGenerated,
                description: "Generated {$quantity} tag(s) for {$data['asset_name']}",
                assetName: $data['asset_name'],
            );

            return $created;
        });
    }
}
