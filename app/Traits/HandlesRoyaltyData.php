<?php

namespace App\Traits;

use PhpOffice\PhpSpreadsheet\Spreadsheet;

trait HandlesRoyaltyData
{
    public function addMnsrTotals($proFormaData)
    {
        // Remove any existing totals rows first to prevent duplication
        $proFormaData = $this->removeExistingTotals($proFormaData);
        
        $totalsRowIndex = count($proFormaData) + 1; // Excel row index (1-based)
        $totalsRow = array_fill(0, max(array_map('count', $proFormaData)), null);

        // Add label to Column C (index 2)
        $totalsRow[2] = 'T O T A L S';

        // Define target columns for summation
        $sumFormulaColumns = [
            37 => 'AL',
            38 => 'AM',
            41 => 'AP',
            42 => 'AQ',
            45 => 'AT',
            46 => 'AU',
        ];

        foreach ($sumFormulaColumns as $colIndex => $colLetter) {
            $totalsRow[$colIndex] = "=SUM({$colLetter}8:{$colLetter}".($totalsRowIndex - 1).')';
        }

        // Add the totals row to the dataset
        $proFormaData[] = $totalsRow;

        return $proFormaData;
    }

    /**
     * Remove existing totals rows from the data to prevent duplication.
     * Identifies totals rows by checking for "T O T A L S" in column C (index 2).
     *
     * @param array $data The data array to clean
     * @return array The data array with totals rows removed
     */
    private function removeExistingTotals(array $data): array
    {
        $cleanedData = [];
        
        foreach ($data as $rowIndex => $row) {
            // Skip rows that contain "T O T A L S" in column C (index 2)
            if (is_array($row) && isset($row[2]) && 
                is_string($row[2]) && trim($row[2]) === 'T O T A L S') {
                continue; // Skip this totals row
            }
            
            $cleanedData[] = $row;
        }
        
        return $cleanedData;
    }

    /**
     * @param  array  $proFormaData  The array representing the worksheet data.
     * @return array The array with computed values.
     */
    public function reApplyMnsrFormulaValues(&$proFormaData)
    {
        for ($i = 7; $i < count($proFormaData); $i++) {
            // For these formulas, we use cells from the same row.
            // Retrieve the common cell for Column A (index 0).
            $valueA = isset($proFormaData[$i][0]) ? $proFormaData[$i][0] : '';

            //
            // --- Column O (index 14) ---
            // =IF($A="","",IF(P=0,"A",IF(P=AZ,"E","A")))
            //
            // P is at index 15 and AZ is at index 51.
            $valueP = isset($proFormaData[$i][15]) ? $proFormaData[$i][15] : 0;
            $valueAZ = isset($proFormaData[$i][51]) ? $proFormaData[$i][51] : 0;
            if ($valueA === '' || $valueA === null) {
                $resultO = '';
            } elseif ($valueP == $valueAZ) {
                $resultO = 'E';
            } elseif ($valueP == 0 || $valueP === '0' || $valueP === '' || $valueP === null) {
                $resultO = 'A';
            } else {
                $resultO = 'A';
            }
            $proFormaData[$i][14] = $resultO;

            //
            // --- Column S (index 18) ---
            // =IF($A="","",IF(T=0,"A",IF(T=BA,"E","A")))
            //
            // T is at index 19 and BA is at index 52.
            $valueT = isset($proFormaData[$i][19]) ? $proFormaData[$i][19] : 0;
            $valueBA = isset($proFormaData[$i][52]) ? $proFormaData[$i][52] : 0;
            if ($valueA === '' || $valueA === null) {
                $resultS = '';
            } elseif ($valueT == $valueBA) {
                $resultS = 'E';
            } elseif ($valueT == 0 || $valueT === '0' || $valueT === '' || $valueT === null) {
                $resultS = 'A';
            } else {
                $resultS = 'A';
            }
            $proFormaData[$i][18] = $resultS;

            //
            // --- Column W (index 22) ---
            // =IF($A="","",IF(X=0,"A",IF(X=BB,"E","A")))
            //
            // X is at index 23 and BB is at index 53.
            $valueX = isset($proFormaData[$i][23]) ? $proFormaData[$i][23] : 0;
            $valueBB = isset($proFormaData[$i][53]) ? $proFormaData[$i][53] : 0;

            if ($valueA === '' || $valueA === null) {
                $resultW = '';
            } elseif ($valueX == $valueBB) {
                $resultW = 'E';
            } elseif ($valueX == 0 || $valueX === '0' || $valueX === '' || $valueX === null) {
                $resultW = 'A';
            } else {
                $resultW = 'A';
            }

            $proFormaData[$i][22] = $resultW;

            //
            // --- Column AA (index 26) ---
            // =IF($A="","",IF(AB=0,"A",IF(AB=BC,"E","A")))
            //
            // AB is at index 27 and BC is at index 54.
            $valueAB = isset($proFormaData[$i][27]) ? $proFormaData[$i][27] : 0;
            $valueBC = isset($proFormaData[$i][54]) ? $proFormaData[$i][54] : 0;
            if ($valueA === '' || $valueA === null) {
                $resultAA = '';
            } elseif ($valueAB == $valueBC) {
                $resultAA = 'E';
            } elseif ($valueAB == 0 || $valueAB === '0' || $valueAB === '' || $valueAB === null) {
                $resultAA = 'A';
            } else {
                $resultAA = 'A';
            }
            $proFormaData[$i][26] = $resultAA;

            //
            // --- Column AF (index 31) ---
            // =IF($A="","",IF(AG=0,"A",IF(AG=BD,"E","A")))
            //
            // AG is at index 32 and BD is at index 55.
            $valueAG = isset($proFormaData[$i][32]) ? $proFormaData[$i][32] : 0;
            $valueBD = isset($proFormaData[$i][55]) ? $proFormaData[$i][55] : 0;
            if ($valueA === '' || $valueA === null) {
                $resultAF = '';
            } elseif ($valueAG == $valueBD) {
                $resultAF = 'E';
            } elseif ($valueAG == 0 || $valueAG === '0' || $valueAG === '' || $valueAG === null) {
                $resultAF = 'A';
            } else {
                $resultAF = 'A';
            }
            $proFormaData[$i][31] = $resultAF;

            //
            // --- Column AL (index 37) ---
            // =IF(O="A",P,0)+IF(S="A",T,0)+IF(W="A",X,0)+IF(AA="A",AB,0)+IF(AF="A",AG,0)
            //
            // Here we use our computed results in O, S, W, AA and AF.
            // P is at index 15; T is at index 19; X is at index 23; AB is at index 27; AG is at index 32.
            $sumAL = 0;
            if ($resultO === 'A') {
                $sumAL += (is_numeric($valueP) ? $valueP : 0);
            }
            if ($resultS === 'A') {
                $sumAL += (is_numeric($valueT) ? $valueT : 0);
            }
            if ($resultW === 'A') {
                $sumAL += (is_numeric($valueX) ? $valueX : 0);
            }
            if ($resultAA === 'A') {
                $sumAL += (is_numeric($valueAB) ? $valueAB : 0);
            }
            if ($resultAF === 'A') {
                $sumAL += (is_numeric($valueAG) ? $valueAG : 0);
            }
            $proFormaData[$i][37] = $sumAL;

            //
            // --- Column AM (index 38) ---
            // =IF(O="A",Q,0)+IF(S="A",U,0)+IF(W="A",Y,0)+IF(AA="A",AC,0)+IF(AF="A",AH,0)
            //
            // Q is at index 16; U at index 20; Y at index 24; AC at index 28; AH at index 33.
            $valueQ = isset($proFormaData[$i][16]) ? $proFormaData[$i][16] : 0;
            $valueU = isset($proFormaData[$i][20]) ? $proFormaData[$i][20] : 0;
            $valueY = isset($proFormaData[$i][24]) ? $proFormaData[$i][24] : 0;
            $valueAC = isset($proFormaData[$i][28]) ? $proFormaData[$i][28] : 0;
            $valueAH = isset($proFormaData[$i][33]) ? $proFormaData[$i][33] : 0;

            $sumAM = 0;
            if ($resultO === 'A') {
                $sumAM += (is_numeric($valueQ) ? $valueQ : 0);
            }
            if ($resultS === 'A') {
                $sumAM += (is_numeric($valueU) ? $valueU : 0);
            }
            if ($resultW === 'A') {
                $sumAM += (is_numeric($valueY) ? $valueY : 0);
            }
            if ($resultAA === 'A') {
                $sumAM += (is_numeric($valueAC) ? $valueAC : 0);
            }
            if ($resultAF === 'A') {
                $sumAM += (is_numeric($valueAH) ? $valueAH : 0);
            }
            $proFormaData[$i][38] = $sumAM;

            //
            // --- Column AN (index 39) ---
            // =SUM(AL:AM)
            $proFormaData[$i][39] = $sumAL + $sumAM;

            //
            // --- Column AP (index 41) ---
            // =IF(O="E",P,0)+IF(S="E",T,0)+IF(W="E",X,0)+IF(AA="E",AB,0)+IF(AF="E",AG,0)
            $sumAP = 0;
            if ($resultO === 'E') {
                $sumAP += (is_numeric($valueP) ? $valueP : 0);
            }
            if ($resultS === 'E') {
                $sumAP += (is_numeric($valueT) ? $valueT : 0);
            }
            if ($resultW === 'E') {
                $sumAP += (is_numeric($valueX) ? $valueX : 0);
            }
            if ($resultAA === 'E') {
                $sumAP += (is_numeric($valueAB) ? $valueAB : 0);
            }
            if ($resultAF === 'E') {
                $sumAP += (is_numeric($valueAG) ? $valueAG : 0);
            }
            $proFormaData[$i][41] = $sumAP;

            //
            // --- Column AQ (index 42) ---
            // =IF(O="E",Q,0)+IF(S="E",U,0)+IF(W="E",Y,0)+IF(AA="E",AC,0)+IF(AF="E",AH,0)
            $sumAQ = 0;
            if ($resultO === 'E') {
                $sumAQ += (is_numeric($valueQ) ? $valueQ : 0);
            }
            if ($resultS === 'E') {
                $sumAQ += (is_numeric($valueU) ? $valueU : 0);
            }
            if ($resultW === 'E') {
                $sumAQ += (is_numeric($valueY) ? $valueY : 0);
            }
            if ($resultAA === 'E') {
                $sumAQ += (is_numeric($valueAC) ? $valueAC : 0);
            }
            if ($resultAF === 'E') {
                $sumAQ += (is_numeric($valueAH) ? $valueAH : 0);
            }
            $proFormaData[$i][42] = $sumAQ;

            //
            // --- Column AR (index 43) ---
            // =SUM(AP:AQ)
            $proFormaData[$i][43] = $sumAP + $sumAQ;

            //
            // --- Column AV (index 47) ---
            // =SUM(AT:AU)  where AT is at index 45 and AU is at index 46.
            $valueAT = isset($proFormaData[$i][45]) ? $proFormaData[$i][45] : 0;
            $valueAU = isset($proFormaData[$i][46]) ? $proFormaData[$i][46] : 0;
            $proFormaData[$i][47] = (is_numeric($valueAT) ? (float) str_replace(',', '', $valueAT) : null) + (is_numeric($valueAU) ? (float) str_replace(',', '', $valueAU) : null);

        }

        return $proFormaData;
    }

    /**
     * @param  array  $proFormaData  The array representing the worksheet data.
     * @param  int  $i  index to recalculate
     * @return array The array with computed values.
     */
    public function reApplyMnsrFormulaValueAtIndex(&$proFormaData, $i)
    {
        // For these formulas, we use cells from the same row.
        // Retrieve the common cell for Column A (index 0).
        $valueA = isset($proFormaData[$i][0]) ? $proFormaData[$i][0] : '';

        //
        // --- Column O (index 14) ---
        // =IF($A="","",IF(P=0,"A",IF(P=AZ,"E","A")))
        //
        // P is at index 15 and AZ is at index 51.
        $valueP = isset($proFormaData[$i][15]) ? $proFormaData[$i][15] : 0;
        $valueAZ = isset($proFormaData[$i][51]) ? $proFormaData[$i][51] : 0;
        if ($valueA === '' || $valueA === null) {
            $resultO = '';
        } elseif ($valueP == $valueAZ) {
            $resultO = 'E';
        } elseif ($valueP == 0 || $valueP === '0' || $valueP === '' || $valueP === null) {
            $resultO = 'A';
        } else {
            $resultO = 'A';
        }
        $proFormaData[$i][14] = $resultO;

        //
        // --- Column S (index 18) ---
        // =IF($A="","",IF(T=0,"A",IF(T=BA,"E","A")))
        //
        // T is at index 19 and BA is at index 52.
        $valueT = isset($proFormaData[$i][19]) ? $proFormaData[$i][19] : 0;
        $valueBA = isset($proFormaData[$i][52]) ? $proFormaData[$i][52] : 0;
        if ($valueA === '' || $valueA === null) {
            $resultS = '';
        } elseif ($valueT == $valueBA) {
            $resultS = 'E';
        } elseif ($valueT == 0 || $valueT === '0' || $valueT === '' || $valueT === null) {
            $resultS = 'A';
        } else {
            $resultS = 'A';
        }
        $proFormaData[$i][18] = $resultS;

        //
        // --- Column W (index 22) ---
        // =IF($A="","",IF(X=0,"A",IF(X=BB,"E","A")))
        //
        // X is at index 23 and BB is at index 53.
        $valueX = isset($proFormaData[$i][23]) ? $proFormaData[$i][23] : 0;
        $valueBB = isset($proFormaData[$i][53]) ? $proFormaData[$i][53] : 0;

        if ($valueA === '' || $valueA === null) {
            $resultW = '';
        } elseif ($valueX == $valueBB) {
            $resultW = 'E';
        } elseif ($valueX == 0 || $valueX === '0' || $valueX === '' || $valueX === null) {
            $resultW = 'A';
        } else {
            $resultW = 'A';
        }

        $proFormaData[$i][22] = $resultW;

        //
        // --- Column AA (index 26) ---
        // =IF($A="","",IF(AB=0,"A",IF(AB=BC,"E","A")))
        //
        // AB is at index 27 and BC is at index 54.
        $valueAB = isset($proFormaData[$i][27]) ? $proFormaData[$i][27] : 0;
        $valueBC = isset($proFormaData[$i][54]) ? $proFormaData[$i][54] : 0;
        if ($valueA === '' || $valueA === null) {
            $resultAA = '';
        } elseif ($valueAB == $valueBC) {
            $resultAA = 'E';
        } elseif ($valueAB == 0 || $valueAB === '0' || $valueAB === '' || $valueAB === null) {
            $resultAA = 'A';
        } else {
            $resultAA = 'A';
        }
        $proFormaData[$i][26] = $resultAA;

        //
        // --- Column AF (index 31) ---
        // =IF($A="","",IF(AG=0,"A",IF(AG=BD,"E","A")))
        //
        // AG is at index 32 and BD is at index 55.
        $valueAG = isset($proFormaData[$i][32]) ? $proFormaData[$i][32] : 0;
        $valueBD = isset($proFormaData[$i][55]) ? $proFormaData[$i][55] : 0;
        if ($valueA === '' || $valueA === null) {
            $resultAF = '';
        } elseif ($valueAG == $valueBD) {
            $resultAF = 'E';
        } elseif ($valueAG == 0 || $valueAG === '0' || $valueAG === '' || $valueAG === null) {
            $resultAF = 'A';
        } else {
            $resultAF = 'A';
        }
        $proFormaData[$i][31] = $resultAF;

        //
        // --- Column AL (index 37) ---
        // =IF(O="A",P,0)+IF(S="A",T,0)+IF(W="A",X,0)+IF(AA="A",AB,0)+IF(AF="A",AG,0)
        //
        // Here we use our computed results in O, S, W, AA and AF.
        // P is at index 15; T is at index 19; X is at index 23; AB is at index 27; AG is at index 32.
        $sumAL = 0;
        if ($resultO === 'A') {
            $sumAL += (is_numeric($valueP) ? $valueP : 0);
        }
        if ($resultS === 'A') {
            $sumAL += (is_numeric($valueT) ? $valueT : 0);
        }
        if ($resultW === 'A') {
            $sumAL += (is_numeric($valueX) ? $valueX : 0);
        }
        if ($resultAA === 'A') {
            $sumAL += (is_numeric($valueAB) ? $valueAB : 0);
        }
        if ($resultAF === 'A') {
            $sumAL += (is_numeric($valueAG) ? $valueAG : 0);
        }
        $proFormaData[$i][37] = $sumAL;

        //
        // --- Column AM (index 38) ---
        // =IF(O="A",Q,0)+IF(S="A",U,0)+IF(W="A",Y,0)+IF(AA="A",AC,0)+IF(AF="A",AH,0)
        //
        // Q is at index 16; U at index 20; Y at index 24; AC at index 28; AH at index 33.
        $valueQ = isset($proFormaData[$i][16]) ? $proFormaData[$i][16] : 0;
        $valueU = isset($proFormaData[$i][20]) ? $proFormaData[$i][20] : 0;
        $valueY = isset($proFormaData[$i][24]) ? $proFormaData[$i][24] : 0;
        $valueAC = isset($proFormaData[$i][28]) ? $proFormaData[$i][28] : 0;
        $valueAH = isset($proFormaData[$i][33]) ? $proFormaData[$i][33] : 0;

        $sumAM = 0;
        if ($resultO === 'A') {
            $sumAM += (is_numeric($valueQ) ? $valueQ : 0);
        }
        if ($resultS === 'A') {
            $sumAM += (is_numeric($valueU) ? $valueU : 0);
        }
        if ($resultW === 'A') {
            $sumAM += (is_numeric($valueY) ? $valueY : 0);
        }
        if ($resultAA === 'A') {
            $sumAM += (is_numeric($valueAC) ? $valueAC : 0);
        }
        if ($resultAF === 'A') {
            $sumAM += (is_numeric($valueAH) ? $valueAH : 0);
        }
        $proFormaData[$i][38] = $sumAM;

        //
        // --- Column AN (index 39) ---
        // =SUM(AL:AM)
        $proFormaData[$i][39] = $sumAL + $sumAM;

        //
        // --- Column AP (index 41) ---
        // =IF(O="E",P,0)+IF(S="E",T,0)+IF(W="E",X,0)+IF(AA="E",AB,0)+IF(AF="E",AG,0)
        $sumAP = 0;
        if ($resultO === 'E') {
            $sumAP += (is_numeric($valueP) ? $valueP : 0);
        }
        if ($resultS === 'E') {
            $sumAP += (is_numeric($valueT) ? $valueT : 0);
        }
        if ($resultW === 'E') {
            $sumAP += (is_numeric($valueX) ? $valueX : 0);
        }
        if ($resultAA === 'E') {
            $sumAP += (is_numeric($valueAB) ? $valueAB : 0);
        }
        if ($resultAF === 'E') {
            $sumAP += (is_numeric($valueAG) ? $valueAG : 0);
        }
        $proFormaData[$i][41] = $sumAP;

        //
        // --- Column AQ (index 42) ---
        // =IF(O="E",Q,0)+IF(S="E",U,0)+IF(W="E",Y,0)+IF(AA="E",AC,0)+IF(AF="E",AH,0)
        $sumAQ = 0;
        if ($resultO === 'E') {
            $sumAQ += (is_numeric($valueQ) ? $valueQ : 0);
        }
        if ($resultS === 'E') {
            $sumAQ += (is_numeric($valueU) ? $valueU : 0);
        }
        if ($resultW === 'E') {
            $sumAQ += (is_numeric($valueY) ? $valueY : 0);
        }
        if ($resultAA === 'E') {
            $sumAQ += (is_numeric($valueAC) ? $valueAC : 0);
        }
        if ($resultAF === 'E') {
            $sumAQ += (is_numeric($valueAH) ? $valueAH : 0);
        }
        $proFormaData[$i][42] = $sumAQ;

        //
        // --- Column AR (index 43) ---
        // =SUM(AP:AQ)
        $proFormaData[$i][43] = $sumAP + $sumAQ;

        //
        // --- Column AV (index 47) ---
        // =SUM(AT:AU)  where AT is at index 45 and AU is at index 46.
        $valueAT = isset($proFormaData[$i][45]) ? $proFormaData[$i][45] : 0;
        $valueAU = isset($proFormaData[$i][46]) ? $proFormaData[$i][46] : 0;
        $proFormaData[$i][47] = (is_numeric($valueAT) ? (float) str_replace(',', '', $valueAT) : null) + (is_numeric($valueAU) ? (float) str_replace(',', '', $valueAU) : null);


        return $proFormaData;
    }

    public function reApplyMnsrFormulas(&$proFormaData)
    {
        for ($i = 7; $i < count($proFormaData); $i++) {
            $excelRow = $i + 1;

            // Check if both columns A and B are empty, if so stop processing
            $columnA = isset($proFormaData[$i][0]) ? trim($proFormaData[$i][0]) : '';
            $columnB = isset($proFormaData[$i][1]) ? trim($proFormaData[$i][1]) : '';
            
            if (empty($columnA) && empty($columnB)) {
                break;
            }

            // Column O (index 14)
            $proFormaData[$i][14] = '=IF($A'.$excelRow.'="","",IF(P'.$excelRow.'=0,"A",IF(P'.$excelRow.'=AZ'.$excelRow.',"E","A")))';

            // Column S (index 18)
            $proFormaData[$i][18] = '=IF($A'.$excelRow.'="","",IF(T'.$excelRow.'=0,"A",IF(T'.$excelRow.'=BA'.$excelRow.',"E","A")))';

            // Column W (index 22)
            $proFormaData[$i][22] = '=IF($A'.$excelRow.'="","",IF(X'.$excelRow.'=0,"A",IF(X'.$excelRow.'=BB'.$excelRow.',"E","A")))';

            // Column AA (index 26)
            $proFormaData[$i][26] = '=IF($A'.$excelRow.'="","",IF(AB'.$excelRow.'=0,"A",IF(AB'.$excelRow.'=BC'.$excelRow.',"E","A")))';

            // Column AF (index 31)
            $proFormaData[$i][31] = '=IF($A'.$excelRow.'="","",IF(AG'.$excelRow.'=0,"A",IF(AG'.$excelRow.'=BD'.$excelRow.',"E","A")))';

            // Column AL (index 37)
            $proFormaData[$i][37] = '=IF($O'.$excelRow.'="A",P'.$excelRow.',0)+IF($S'.$excelRow.'="A",T'.$excelRow.',0)+IF($W'.$excelRow.'="A",X'.$excelRow.',0)+IF($AA'.$excelRow.'="A",AB'.$excelRow.',0)+IF($AF'.$excelRow.'="A",AG'.$excelRow.',0)';

            // Column AM (index 38)
            $proFormaData[$i][38] = '=IF($O'.$excelRow.'="A",Q'.$excelRow.',0)+IF($S'.$excelRow.'="A",U'.$excelRow.',0)+IF($W'.$excelRow.'="A",Y'.$excelRow.',0)+IF($AA'.$excelRow.'="A",AC'.$excelRow.',0)+IF($AF'.$excelRow.'="A",AH'.$excelRow.',0)';

            // Column AN (index 39)
            $proFormaData[$i][39] = '=SUM(AL'.$excelRow.':AM'.$excelRow.')';

            // Column AP (index 41)
            $proFormaData[$i][41] = '=IF($O'.$excelRow.'="E",P'.$excelRow.',0)+IF($S'.$excelRow.'="E",T'.$excelRow.',0)+IF($W'.$excelRow.'="E",X'.$excelRow.',0)+IF($AA'.$excelRow.'="E",AB'.$excelRow.',0)+IF($AF'.$excelRow.'="E",AG'.$excelRow.',0)';

            // Column AQ (index 42)
            $proFormaData[$i][42] = '=IF($O'.$excelRow.'="E",Q'.$excelRow.',0)+IF($S'.$excelRow.'="E",U'.$excelRow.',0)+IF($W'.$excelRow.'="E",Y'.$excelRow.',0)+IF($AA'.$excelRow.'="E",AC'.$excelRow.',0)+IF($AF'.$excelRow.'="E",AH'.$excelRow.',0)';

            // Column AR (index 43)
            $proFormaData[$i][43] = '=SUM(AP'.$excelRow.':AQ'.$excelRow.')';

            // Column AV (index 47)
            $proFormaData[$i][47] = '=SUM(AT'.$excelRow.':AU'.$excelRow.')';

            // Column AW (index 48)
            $proFormaData[$i][48] = '=IF(AV'.$excelRow.'=0,"",IF(AN'.$excelRow.'=AV'.$excelRow.',"","W"))';
        }

        return $proFormaData;
    }

    /**
     * Convert Serial Number into Date
     * Used for converting dates read from Excel (read as serial number)
     *
     * @return string|null
     */
    public function convertSerialToDate($serialNumber)
    {
        if ($serialNumber == null) {
            return null;
        }

        $unixTimestamp = (intval($serialNumber) - 25569) * 86400;
        $date = date('Y-m-d', $unixTimestamp);

        return $date;
    }

    public function convertBulk($dataRows)
    {
        $spreadsheet = new Spreadsheet;
        $worksheet = $spreadsheet->getActiveSheet();

        // Load all data in one go
        $worksheet->fromArray($dataRows, null, 'A1');

        // Get calculated values
        $results = [];
        $highestRow = $worksheet->getHighestRow();
        $highestCol = $worksheet->getHighestColumn();
        $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);

        for ($row = 1; $row <= $highestRow; $row++) {
            $results[$row - 1] = [];
            // Use numeric column iteration instead of letter iteration
            for ($col = 1; $col <= $highestColIndex; $col++) {
                $cell = $worksheet->getCellByColumnAndRow($col, $row);

                // Get the formatted value (as Excel would display it)
                $cellValue = $cell->getCalculatedValue();

                //                // If it's a numeric value, try to convert back to number
                //                if (is_numeric($cellValue)) {
                //                    $cellValue = (float) $cellValue;
                //                }

                $results[$row - 1][] = $cellValue;
            }
        }

        return $results;
    }
}
