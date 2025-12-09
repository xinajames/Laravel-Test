<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self CompanyOwnedJFC()
 * @method static self CompanyOwnedBGC()
 * @method static self FranchiseeFZE()
 */
class StoreGroupEnum extends Enum
{
    public static function getDescription($value): string
    {
        $descriptions = [
            self::CompanyOwnedJFC()->value => 'Company Owned - JFC',
            self::CompanyOwnedBGC()->value => 'Company Owned - BGC',
            self::FranchiseeFZE()->value => 'Franchisee - FZE',
        ];

        return $descriptions[$value] ?? 'Unknown Store Group';
    }

    protected static function values(): array
    {
        return [
            'CompanyOwnedJFC' => 'CompanyOwnedJFC',
            'CompanyOwnedBGC' => 'CompanyOwnedBGC',
            'FranchiseeFZE' => 'FranchiseeFZE',
        ];
    }
}
