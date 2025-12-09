<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self Branch()
 * @method static self Express()
 * @method static self Junior()
 * @method static self Outlet()
 * @method static self PeddlingCart()
 * @method static self MallCart()
 * @method static self MobileVan()
 * @method static self MotorizedCart()
 * @method static self Kiosk()
 * @method static self RollingStore()
 */
class StoreTypeEnum extends Enum
{
    public static function getDescription($value): string
    {
        $descriptions = [
            self::Branch()->value => 'Branch',
            self::Express()->value => 'Express',
            self::Junior()->value => 'Junior',
            self::Outlet()->value => 'Outlet',
            self::PeddlingCart()->value => 'Peddling Cart',
            self::MallCart()->value => 'Mall Cart',
            self::MobileVan()->value => 'Mobile Van',
            self::MotorizedCart()->value => 'Motorized Cart',
            self::Kiosk()->value => 'Kiosk',
            self::RollingStore()->value => 'Rolling Store',
        ];

        return $descriptions[$value] ?? 'Unknown Store Type';
    }

    protected static function values(): array
    {
        return [
            'Branch' => 'Branch',
            'Express' => 'Express',
            'Junior' => 'Junior',
            'Outlet' => 'Outlet',
            'PeddlingCart' => 'Peddling Cart',
            'MallCart' => 'Mall Cart',
            'MobileVan' => 'Mobile Van',
            'MotorizedCart' => 'Motorized Cart',
            'Kiosk' => 'Kiosk',
            'RollingStore' => 'Rolling Store',
        ];
    }

    public static function getStatusType(string $key)
    {
        return self::values()[$key] ?? null;
    }
}
