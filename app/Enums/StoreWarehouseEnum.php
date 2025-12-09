<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self PAM()
 * @method static self PSG()
 * @method static self MDE()
 * @method static self DVO()
 * @method static self CGY()
 * @method static self TAC()
 * @method static self DPL()
 * @method static self CAR()
 */
class StoreWarehouseEnum extends Enum
{
    public static function getDescription($value): string
    {
        $descriptions = [
            self::PAM()->value => 'PAM',
            self::PSG()->value => 'PSG',
            self::MDE()->value => 'MDE',
            self::DVO()->value => 'DVO',
            self::CGY()->value => 'CGY',
            self::TAC()->value => 'TAC',
            self::DPL()->value => 'DPL',
            self::CAR()->value => 'CAR',
        ];

        return $descriptions[$value] ?? 'Unknown Store Warehouse';
    }

    protected static function values(): array
    {
        return [
            'PAM' => 'PAM',
            'PSG' => 'PSG',
            'MDE' => 'MDE',
            'DVO' => 'DVO',
            'CGY' => 'CGY',
            'TAC' => 'TAC',
            'DPL' => 'DPL',
            'CAR' => 'CAR',
        ];
    }
}
