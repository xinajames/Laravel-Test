<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self Bread()
 * @method static self NonBread()
 */
class SalesTypeEnum extends Enum
{
    protected static function values(): array
    {
        return [
            'Bread' => 1,
            'NonBread' => 2,
        ];
    }

    public static function getDescription($value): string
    {
        $descriptions = [
            self::Bread()->value => 'Bread',
            self::NonBread()->value => 'Non Bread',
        ];

        return $descriptions[$value] ?? 'Unknown Sales Type Option';
    }
}
