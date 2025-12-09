<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self BranchFranMaster()
 * @method static self ProFormaNationalSalesByStore()
 * @method static self JBSSalesHistoryByStore()
 * @method static self MNSR()
 * @method static self JBMISData()
 * @method static self JBMISCodeConversion()
 * @method static self POSData()
 * @method static self Royalty()
 * @method static self ProFormaRoyaltyWorkbook()
 * @method static self JBSSalesHistory()
 */
class MacroFileTypeEnum extends Enum
{
    protected static function values(): array
    {
        return [
            'BranchFranMaster' => 1,
            'ProFormaNationalSalesByStore' => 2,
            'JBSSalesHistoryByStore' => 3,
            'MNSR' => 4,
            'JBMISData' => 5,
            'JBMISCodeConversion' => 6,
            'POSData' => 7,
            'Royalty' => 8,
            'ProFormaRoyaltyWorkbook' => 9,
            'JBSSalesHistory' => 10, // by Branch
        ];
    }

    protected static function labels(): array
    {
        return [
            'BranchFranMaster' => 'Branch Fran Master',
            'ProFormaNationalSalesByStore' => 'Pro Forma National Sales by Store',
            'JBSSalesHistoryByStore' => 'JBS Sales History By Store',
            'MNSR' => 'Monthly National Sales Report',
            'JBMISData' => 'JBMIS Data',
            'JBMISCodeConversion' => 'JBMIS Code Conversion',
            'POSData' => 'POS Data',
            'Royalty' => 'Royalty Workbook',
            'ProFormaRoyaltyWorkbook' => 'Pro Forma Royalty Workbook',
            'JBSSalesHistory' => 'JBS Sales History', // by Branch
        ];
    }
}
