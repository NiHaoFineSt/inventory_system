<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    protected $fillable = [
        'category',
        'asset_name',
        'tracking_number',
        'unit_number',
        'status',
        'condition',
        'location',
        'qr_code_payload',
        'cost',
    ];
}