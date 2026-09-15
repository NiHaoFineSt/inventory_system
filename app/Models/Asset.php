<?php

namespace App\Models;

use App\Enums\AssetCondition;
use App\Enums\AssetStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'asset_name',
        'tracking_number',
        'qr_code_payload',
        'prefix',
        'unit_number',
        'status',
        'condition',
        'location',
        'assigned_to',
    ];

    protected function casts(): array
    {
        return [
            'status' => AssetStatus::class,
            'condition' => AssetCondition::class,
        ];
    }

    public function damageReports(): HasMany
    {
        return $this->hasMany(DamageReport::class, 'tracking_number', 'tracking_number');
    }
}
