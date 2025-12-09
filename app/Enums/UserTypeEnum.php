<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self Admin()
 */
class UserTypeEnum extends Enum
{
    protected static function values(): array
    {
        return [
            'Admin' => 1,
        ];
    }
}
