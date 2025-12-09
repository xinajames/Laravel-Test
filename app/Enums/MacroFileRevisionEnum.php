<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self BranchFranMasterDefault()
 * @method static self ProFormaNationalSalesByStoreDefault()
 * @method static self JBSSalesHistoryByStoreDefault()
 * @method static self JBSSalesHistoryByStoreDefaultUpdated()
 * @method static self MNSRDefault()
 * @method static self MNSRAddedFranchiseeData()
 * @method static self MNSRAddedJBMISData()
 * @method static self MNSRCreatedRoyaltyData()
 * @method static self MNSRUpdatedRoyaltyData()
 * @method static self JBMISDataDefault()
 * @method static self JBMISCodeConversionDefault()
 * @method static self POSDataDefault()
 * @method static self RoyaltyDefault()
 * @method static self RoyaltyUpdated()
 * @method static self ProFormaRoyaltyWorkbookDefault()
 * @method static self JBSSalesHistoryDefault()
 * @method static self JBSSalesHistoryUpdated()
 */
class MacroFileRevisionEnum extends Enum
{
    protected static function values(): array
    {
        return [
            'BranchFranMasterDefault' => 1,
            'ProFormaNationalSalesByStoreDefault' => 2,

            'JBSSalesHistoryByStoreDefault' => 3,
            'JBSSalesHistoryByStoreDefaultUpdated' => 4,

            'MNSRDefault' => 5,
            'MNSRAddedFranchiseeData' => 6,
            'MNSRAddedJBMISData' => 7,
            'MNSRCreatedRoyaltyData' => 8,
            'MNSRUpdatedRoyaltyData' => 9,

            'JBMISDataDefault' => 10,
            'JBMISCodeConversionDefault' => 11,

            'POSDataDefault' => 12,

            'RoyaltyDefault' => 13,
            'RoyaltyUpdated' => 14,

            'ProFormaRoyaltyWorkbookDefault' => 15,

            'JBSSalesHistoryDefault' => 16,
            'JBSSalesHistoryUpdated' => 17,
        ];
    }
}
