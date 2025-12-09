<?php

namespace App\Import\DataMigration;

use Maatwebsite\Excel\Concerns\SkipsUnknownSheets;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class JFMDataMigrationImport implements SkipsUnknownSheets, WithMultipleSheets
{
    private $sheets;

    public function sheets(): array
    {
        $this->sheets = [
            'Updated Franchisee Profile' => new JFMFranchiseeProfileDataMigrationSheet,
            'Updated Store Profile' => new JFMStoreProfileDataMigrationSheet,
        ];

        return $this->sheets;
    }

    public function onUnknownSheet($sheetName) {}

    public function getSheetDatas()
    {
        return [
            'Updated Franchisee Profile' => $this->sheets['Updated Franchisee Profile']->getData(),
            'Updated Store Profile' => $this->sheets['Updated Store Profile']->getData(),
        ];
    }
}
