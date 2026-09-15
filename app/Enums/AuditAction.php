<?php

namespace App\Enums;

enum AuditAction: string
{
    case Added = 'ADDED';
    case Edited = 'EDITED';
    case Deleted = 'DELETED';
    case Reported = 'REPORTED';
    case TagGenerated = 'TAG_GENERATED';
    case Assigned = 'ASSIGNED';
    case StatusChanged = 'STATUS_CHANGED';
}
