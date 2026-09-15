<?php

namespace App\Enums;

enum UserRole: string
{
    case Staff = 'staff';
    case AssetTeam = 'asset_team';

    public function label(): string
    {
        return match ($this) {
            self::Staff => 'General Staff',
            self::AssetTeam => 'Asset Team',
        };
    }
}
