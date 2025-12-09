<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self SocialMedia()
 * @method static self PFA()
 * @method static self FranchiseExhibit()
 * @method static self FamilyOrFriends()
 */
class SourceOfInformationEnum extends Enum
{
    public static function getDescription($value): string
    {
        $descriptions = [
            self::SocialMedia()->value => 'Social Media',
            self::PFA()->value => 'PFA',
            self::FranchiseExhibit()->value => 'Franchise Exhibit',
            self::FamilyOrFriends()->value => 'Family or Friends',
        ];

        return $descriptions[$value] ?? 'Unknown Source Of Information';
    }

    protected static function values(): array
    {
        return [
            'SocialMedia' => 'Social Media',
            'PFA' => 'PFA',
            'FranchiseExhibit' => 'Franchise Exhibit',
            'FamilyOrFriends' => 'Family or Friends',

        ];
    }
}
