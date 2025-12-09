<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self Store()
 * @method static self Franchisee()
 */
class ReminderTypeEnum extends Enum
{
    public static function getDescription($value): string
    {
        $descriptions = [
            self::Store()->value => 'Store',
            self::Franchisee()->value => 'Franchisee',
        ];

        return $descriptions[$value] ?? 'Unknown Reminder Type';
    }
}
