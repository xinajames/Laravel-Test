<?php

namespace App\Traits;

use App\Enums\MacroFileTypeEnum;
use Exception;

trait ManageRoyaltyFiles
{
    /**
     * @throws Exception
     */
    private function getUploadedFileType($file_name): MacroFileTypeEnum
    {
        if (preg_match('/^JBMIS-Data-[A-Za-z0-9]{3}-[A-Za-z0-9]{3}-\d{4}(?:\.xlsx)?$/', $file_name)) {
            return MacroFileTypeEnum::JBMISData();
        } elseif (preg_match('/^POS-Data-[A-Za-z0-9]{3}-[A-Za-z0-9]{3}-\d{4}(?:\.xlsx)?$/', $file_name)) {
            return MacroFileTypeEnum::POSData();
        } elseif (preg_match('/^Monthly-Natl-Sales-Rept-[A-Za-z]{3}-\d{4}(?:\.xlsx)?$/', $file_name)) {
            return MacroFileTypeEnum::MNSR();
        }

        throw new Exception('At least one or more File Name is found Invalid for Royalty Generation');
    }
}
