<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self Open()
 * @method static self Future()
 * @method static self TemporaryClosed()
 * @method static self Closed()
 * @method static self Deactivated()
 */
class StoreStatusEnum extends Enum
{
    public static function getDescription($value): string
    {
        $descriptions = [
            self::Open()->value => 'Open',
            self::Future()->value => 'Future',
            self::TemporaryClosed()->value => 'Temporary Closed',
            self::Closed()->value => 'Closed',
            self::Deactivated()->value => 'Deactivated',
        ];

        return $descriptions[$value] ?? 'Unknown Store Status';
    }

    protected static function values(): array
    {
        return [
            'Open' => 'Open',
            'Future' => 'Future',
            'TemporaryClosed' => 'TemporaryClosed',
            'Closed' => 'Closed',
            'Deactivated' => 'Deactivated',
        ];
    }

    public static function getStatusType(string $key)
    {
        return self::values()[$key] ?? null;
    }
}
