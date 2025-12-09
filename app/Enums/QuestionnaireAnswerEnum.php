<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self Yes()
 * @method static self No()
 * @method static self NotApplicable()
 */
class QuestionnaireAnswerEnum extends Enum
{
    protected static function values(): array
    {
        return [
            'Yes' => 'Yes',
            'No' => 'No',
            'NotApplicable' => 'Not Applicable',
        ];
    }
}
