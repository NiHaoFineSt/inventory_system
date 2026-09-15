<?php

namespace App\Enums;

enum AssetCondition: string
{
    case Good = 'Good';
    case Fair = 'Fair';
    case Damaged = 'Damaged';
}
