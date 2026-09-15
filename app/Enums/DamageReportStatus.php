<?php

namespace App\Enums;

enum DamageReportStatus: string
{
    case Pending = 'Pending';
    case UnderInspection = 'Under Inspection';
    case Resolved = 'Resolved';
}
