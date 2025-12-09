<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self CGL()
 * @method static self Fire()
 * @method static self GPA()
 */
class StoreInsuranceTypeEnum extends Enum
{
    public static function getDescription($value): string
    {
        $descriptions = [
            self::CGL()->value => 'CGL',
            self::Fire()->value => 'Fire',
            self::GPA()->value => 'GPA',
        ];

        return $descriptions[$value] ?? 'Unknown Store Insurance Type';
    }

    protected static function values(): array
    {
        return [
            'CGL' => 'CGL',
            'Fire' => 'Fire',
            'GPA' => 'GPA',
        ];
    }
}
