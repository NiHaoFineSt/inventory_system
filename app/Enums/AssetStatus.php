<?php

namespace App\Enums;

enum AssetStatus: string
{
    case Active = 'Active';
    case Inactive = 'Inactive';
    case Maintenance = 'Maintenance';
    case Disposed = 'Disposed';
}
