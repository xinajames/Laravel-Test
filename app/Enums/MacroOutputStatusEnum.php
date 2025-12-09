<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self Pending()
 * @method static self Ongoing()
 * @method static self Successful()
 * @method static self Failed()
 */
class MacroOutputStatusEnum extends Enum
{
    protected static function values(): array
    {
        return [
            'Pending' => 1,
            'Ongoing' => 2,
            'Successful' => 3,
            'Failed' => 4,
        ];
    }
}
