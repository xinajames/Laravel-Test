<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self BasicDetails()
 * @method static self FranchiseeInfo()
 * @method static self Requirements()
 * @method static self Finished()
 */
class FranchiseeApplicationStepEnum extends Enum
{
    protected static function values(): array
    {
        return [
            'BasicDetails' => 'basic-details',
            'FranchiseeInfo' => 'franchisee-info',
            'Requirements' => 'requirements',
            'Finished' => 'finished',
        ];
    }

    public static function getDescription($value): string
    {
        $descriptions = [
            self::BasicDetails()->value => 'Basic Details',
            self::FranchiseeInfo()->value => 'Franchisee Info',
            self::Requirements()->value => 'Requirements',
            self::Finished()->value => 'Finished',
        ];

        return $descriptions[$value] ?? 'Unknown Franchisee Application Step';
    }
}
