<?php

namespace Database\Seeders;

use App\Traits\ManageFilesystems;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class MacroFixedCacheSeeder extends Seeder
{
    use ManageFilesystems;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Enable huge document parsing
        libxml_use_internal_errors(true);

        // Template paths from storage/app/private/royalty/cache
        $templateBasePath = storage_path('app/private/royalty/cache/');

        /** Read ProForma Template */
        $proFormaMonthlySalesByStorePath = $templateBasePath . 'Z-ProForma-Natl-Monthly-Sales-by-Store.xlsx';
        if (!file_exists($proFormaMonthlySalesByStorePath)) {
            throw new \RuntimeException('ProForma template not found: ' . $proFormaMonthlySalesByStorePath);
        }
        $proFormaMonthlySalesByStoreFile = Excel::toCollection([], $proFormaMonthlySalesByStorePath, null, \Maatwebsite\Excel\Excel::XLSX);
        $proFormaData = $proFormaMonthlySalesByStoreFile[0];

        /** Read BranchFranMaster */
        //        $branchFranMasterPath = $templateBasePath . 'Branch-Fran-Master.xlsx';
        //        if (!file_exists($branchFranMasterPath)) {
        //            throw new \RuntimeException('Branch-Fran-Master template not found: ' . $branchFranMasterPath);
        //        }
        //        $branchMasterFile = $this->readLargeExcelFile($branchFranMasterPath);
        //
        //        $branchesData = $branchMasterFile[1]; // data starts at index 1 [column B]
        //        $branchHistoriesData = $branchMasterFile[2]; // data starts at index  1 [column B]
        //        $branchAddressesData = $branchMasterFile[3]; // data starts at index 9 [column J]
        //        $franchiseesData = $branchMasterFile[4]; // data starts at index 8 [column I]

        /** Read Sales History By Store */
        $salesHistoryByStorePath = $templateBasePath . 'JBS-Sales-History-By-Store.xlsx';
        if (!file_exists($salesHistoryByStorePath)) {
            throw new \RuntimeException('Sales History By Store template not found: ' . $salesHistoryByStorePath);
        }
        $salesHistoryByStoreData = Excel::toCollection([], $salesHistoryByStorePath, null, \Maatwebsite\Excel\Excel::XLSX);

        /** Read JBMIS Code Conversion Table File */
        //        $jbmisCodeConvTableFilePath = $templateBasePath . 'X-JBMIS-Code-Conv-Table.xlsx';
        //        if (!file_exists($jbmisCodeConvTableFilePath)) {
        //            throw new \RuntimeException('JBMIS Code Conversion Table not found: ' . $jbmisCodeConvTableFilePath);
        //        }
        //        $jbmisCodeConvTableFile = Excel::toCollection([], $jbmisCodeConvTableFilePath, null, \Maatwebsite\Excel\Excel::XLSX);
        //        $jbmisCodeConvTableData = $jbmisCodeConvTableFile[0];

        // Base path for fixed caches
        $basePath = $this->generateUploadBasePath() . '/royalty/fixed-caches/';

        // Prepare data
        $cacheData = [
            //            'branchfranmaster-data' => [
            //                $branchesData,
            //                $branchHistoriesData,
            //                $branchAddressesData,
            //                $franchiseesData,
            //            ],
            'proforma-natl-sales-by-store-template' => $proFormaData,
            'jbs-sales-history-by-store-data' => $salesHistoryByStoreData,
            // 'jbmis-code-conversion-data' => $jbmisCodeConvTableData,
        ];

        // Upload each cache file using the uploadData method
        foreach ($cacheData as $filename => $data) {
            $jsonData = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $filePath = $basePath . $filename . '.json';

            $this->uploadData($jsonData, $filePath);
        }
    }

    private function readLargeExcelFile($path)
    {
        // Set libxml options for huge documents
        $previousValue = libxml_disable_entity_loader(true);

        try {
            return Excel::toCollection([], $path, null, \Maatwebsite\Excel\Excel::XLSX);
        } finally {
            libxml_disable_entity_loader($previousValue);
        }
    }
}
