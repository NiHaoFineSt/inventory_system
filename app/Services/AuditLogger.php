<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\User;

class AuditLogger
{
    public function log(
        User $user,
        AuditAction $action,
        string $description,
        ?string $assetName = null,
        ?string $trackingNumber = null,
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $user->id,
            'action' => $action,
            'asset_name' => $assetName,
            'tracking_number' => $trackingNumber,
            'performed_by' => $user->name ?? $user->email,
            'description' => $description,
        ]);
    }
}
