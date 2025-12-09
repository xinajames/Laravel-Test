<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self Active()
 * @method static self Inactive()
 * @method static self Separated()
 */
class FranchiseeStatusEnum extends Enum
{
    protected static function values(): array
    {
        return [
            'Active' => 1,
            'Inactive' => 2,
            'Separated' => 3,
        ];
    }

    public static function getDescription($value): string
    {
        $descriptions = [
            self::Active()->value => 'Active',
            self::Inactive()->value => 'Inactive',
            self::Separated()->value => 'Separated',
        ];

        return $descriptions[$value] ?? 'Unknown Franchisee Status';
    }

    public static function getStatusType($value)
    {
        $statuses = self::values();

        // Check if value is an integer and return corresponding key
        $key = array_search($value, $statuses, true);

        return $key !== false ? $key : null;
    }
}
