<?php

namespace App\Export\MNSR;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class PosBgcDataExport implements FromCollection, WithColumnFormatting, WithHeadings, WithMapping, WithStrictNullComparison, WithTitle
{
    private $entries;

    protected $column_count;

    protected $row_count;

    public function __construct($entries)
    {
        $this->entries = $entries;
        $this->column_count = count($this->headings());
        $this->row_count = count($entries);
    }

    public function collection(): Collection
    {
        return collect($this->entries);
    }

    public function title(): string
    {
        return 'POS';
    }

    public function headings(): array
    {
        return [
            'Date',
            'Branch Code',
            'Branch Name',
            'Bread',
            'Ice Cream',
            'Others',
            'Softdrinks',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_NUMBER_00,
            'E' => NumberFormat::FORMAT_NUMBER_00,
            'F' => NumberFormat::FORMAT_NUMBER_00,
            'G' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }

    public function map($row): array
    {
        return [
            $row['date'],
            $row['branch_code'],
            $row['branch_name'],
            number_format((float) $row['bread_total'], 2, '.', ''),
            number_format((float) $row['ice_cream_total'], 2, '.', ''),
            number_format((float) $row['softdrinks_total'], 2, '.', ''),
            number_format((float) $row['others_total'], 2, '.', ''),
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $highestColumn = $sheet->getHighestColumn();
                for ($column = 'A'; $column <= $highestColumn; $column++) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }
                $sheet->calculateColumnWidths();
            },
        ];
    }
}
