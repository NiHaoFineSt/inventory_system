<?php

namespace App\Models;

use App\Enums\DamageReportStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DamageReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reported_by',
        'tracking_number',
        'asset_name',
        'issue_type',
        'description',
        'image_path',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => DamageReportStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}