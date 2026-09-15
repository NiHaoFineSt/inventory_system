<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'status',
    ];
}