<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self Active()
 * @method static self Inactive()
 * @method static self Deactivated()
 * @method static self Expired()
 */
class UserStatusEnum extends Enum
{
    protected static function values(): array
    {
        return [
            'Active' => 1,
            'Inactive' => 2,
            'Deactivated' => 3,
            'Expired' => 4,
        ];
    }

    public static function getDescription($value): string
    {
        $descriptions = [
            self::Active()->value => 'Active',
            self::Inactive()->value => 'Inactive',
            self::Deactivated()->value => 'Deactivated',
            self::Expired()->value => 'Expired',
        ];

        return $descriptions[$value] ?? 'Unknown User Status';
    }
}
