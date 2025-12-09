<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self AuthorizedProducts()
 * @method static self CleanlinessSanitationMaintenance()
 * @method static self ProductionQuality()
 * @method static self OperationalExcellenceFoodSafety()
 * @method static self CustomerExperience()
 * @method static self Finished()
 */
class StoreRatingStepEnum extends Enum
{
    protected static function values(): array
    {
        return [
            'AuthorizedProducts' => 'authorized-products',
            'CleanlinessSanitationMaintenance' => 'cleanliness-sanitation-maintenance',
            'ProductionQuality' => 'production-quality',
            'OperationalExcellenceFoodSafety' => 'operational-excellence-food-safety',
            'CustomerExperience' => 'customer-experience',
            'Finished' => 'finished',
        ];
    }

    public static function getDescription($value): string
    {
        $descriptions = [
            self::AuthorizedProducts()->value => 'Authorized Products',
            self::CleanlinessSanitationMaintenance()->value => 'Cleanliness, Sanitation and Maintenance',
            self::ProductionQuality()->value => 'Production Quality',
            self::OperationalExcellenceFoodSafety()->value => 'Operational Excellence and Food Safety',
            self::CustomerExperience()->value => 'Customer Experience',
            self::Finished()->value => 'Finished',

        ];

        return $descriptions[$value] ?? 'Unknown Store Rating Step';
    }
}
