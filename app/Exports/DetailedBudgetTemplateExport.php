<?php
// app/Exports/DetailedBudgetTemplateExport.php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class DetailedBudgetTemplateExport implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnFormatting, ShouldAutoSize
{
    public function array(): array
    {
        return [
            ['PLN-0001-Bldg-1.1', 'Excavation in Soil and in all.', 'Cum', '1', '100.00', '-', '18.00'],
            ['PLN-0001-Bldg-1.2', 'Shifting of stacked material from inside to the designated external location, including transportation and proper dumping.', 'Cum', '1', '250.00', '5.00', ''],
            ['PLN-0001-Bldg-1.3', 'Excavation in Soil and in all types of strata which can be removed by bucket.', 'Cum', '952.03', '350.00', '5.00', '-'],
            ['', 'Excavation in Hard Rock in all types of strata which can be removed by Needle and Control Blasting.', '', '', '', '', ''],
            ['PLN-0001-Bldg-1.4', 'Upto 5m Depth', 'Cum', '634.69', '600.00', '-', '18.00'],
            ['PLN-0001-Bldg-1.5', 'Depth - 5m to 8m', 'Cum', '952.03', '800.00', '-', '18.00'],
            ['PLN-0001-Bldg-1.6', 'Depth - above 8m', 'Cum', '951.44', '950.00', '-', '18.00'],
            ['PLN-0001-Bldg-2.1', 'M10 (1:3:6) grade for footings', 'Cum', '15.00', '4500.00', '3.00', '18.00'],
            ['PLN-0001-Bldg-2.2', 'M15 (1:2:4) grade for columns', 'Cum', '25.00', '5200.00', '3.00', '18.00'],
            ['PLN-0001-Bldg-2.3', 'M20 (1:1.5:3) grade for slabs', 'Cum', '30.00', '5800.00', '3.00', '18.00'],
            ['PLN-0001-Bldg-3.1', 'Supply of Reinforcement Steel (Fe-500)', 'MT', '10.50', '58000.00', '5.00', '18.00'],
            ['PLN-0001-Bldg-3.2', 'Fabrication & Installation', 'MT', '10.50', '1500.00', '3.00', '18.00'],
            ['', 'MASONRY WORK', '', '', '', '', ''],
            ['PLN-0001-Bldg-4.1', 'First Class Brickwork in CM 1:6', 'Cum', '120.00', '8500.00', '3.00', '12.00'],
            ['PLN-0001-Bldg-4.2', 'Hollow Block Work', 'Cum', '85.00', '7500.00', '3.00', '12.00'],
            ['PLN-0001-Bldg-5.1', 'Internal Plaster 12mm thick', 'Sqm', '450.00', '180.00', '2.50', '18.00'],
            ['PLN-0001-Bldg-5.2', 'External Plaster 20mm thick', 'Sqm', '320.00', '220.00', '2.50', '18.00'],
        ];
    }

    public function headings(): array
    {
        return [
            'WBS',
            'Description',
            'Unit',
            'Quantity',
            'Rates',
            'Retention %',
            'GST %'
        ];
    }

    public function title(): string
    {
        return 'Detailed Budget Template';
    }

     public function styles(Worksheet $sheet)
    {
        // Get total rows
        $totalRows = count($this->array()) + 1;

        // Add borders to all cells
        $sheet->getStyle("A1:G{$totalRows}")->getBorders()->applyFromArray([
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => '000000'],
            ],
        ]);

        // Wrap text in description column
        $sheet->getStyle("B2:B{$totalRows}")->getAlignment()->setWrapText(true);

        // Set alignment for numeric columns (right aligned)
        $sheet->getStyle("D2:G{$totalRows}")->getAlignment()->setHorizontal(
            \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT
        );

        // Set alignment for text columns (left aligned)
        $sheet->getStyle("A2:C{$totalRows}")->getAlignment()->setHorizontal(
            \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT
        );

        // Center align header
        $sheet->getStyle("A1:G1")->getAlignment()->setHorizontal(
            \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
        );

        // Optional: Make header bold
        $sheet->getStyle("A1:G1")->getFont()->setBold(true);

        // Optional: Set row heights
        $sheet->getDefaultRowDimension()->setRowHeight(20);
        $sheet->getRowDimension(1)->setRowHeight(25);
    }

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2, // Quantity
            'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2, // Rates
            'F' => '0.00',  // Retention %
            'G' => '0.00',  // GST %
        ];
    }
}
