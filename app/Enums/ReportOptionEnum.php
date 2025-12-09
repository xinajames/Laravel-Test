<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self GanvioReport()
 * @method static self BarangayWithJbs()
 * @method static self Renewals()
 * @method static self JBSSalesPerformance()
 * @method static self FranchiseeReport()
 * @method static self InsuranceReport()
 * @method static self ContractOfLeaseReport()
 */
class ReportOptionEnum extends Enum
{
    protected static function values(): array
    {
        return [
            'GanvioReport' => 1,
            'BarangayWithJbs' => 2,
            'Renewals' => 3,
            'JBSSalesPerformance' => 4,
            'FranchiseeReport' => 5,
            'InsuranceReport' => 6,
            'ContractOfLeaseReport' => 7,
        ];
    }

    public static function getDescription($value): string
    {
        $descriptions = [
            self::GanvioReport()->value => 'Ganvio Report',
            self::BarangayWithJbs()->value => 'Barangay With JBS Store',
            self::Renewals()->value => 'Renewals Report',
            self::JBSSalesPerformance()->value => 'JBS Sales Performance',
            self::FranchiseeReport()->value => 'Franchisee Report',
            self::InsuranceReport()->value => 'Insurance Report',
            self::ContractOfLeaseReport()->value => 'Contract of Lease Report',
        ];

        return $descriptions[$value] ?? 'Unknown Report Option';
    }
}
