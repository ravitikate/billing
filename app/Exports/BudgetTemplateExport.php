<?php
// app/Exports/BudgetTemplateExport.php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class BudgetTemplateExport implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnFormatting, ShouldAutoSize
{
    public function array(): array
    {
        return [
            [1, 'Earth Work', 'PL1378-Bldg-1', 2808329, 'Building'],
            [2, 'RCC & Steel Structure Work', 'PL1378-Bldg-2', 83701767, 'Building'],
            [3, 'Masonry, Plaster Work', 'PL1378-Bldg-3', 8969395, 'Building'],
            [4, 'Waterproofing Work', 'PL1378-Bldg-4', 1907426, 'Building'],
            [5, 'Doors & Wooden Work', 'PL1378-Bldg-5', 1634343, 'Building'],
            [6, 'Windows & Sliding Doors', 'PL1378-Bldg-6', 639414, 'Building'],
            [7, 'Flooring and Tiling Work', 'PL1378-Bldg-7', 6997455, 'Building'],
            [8, 'MS & SS  Works- Grills & Railings', 'PL1378-Bldg-8', 1341716.777, 'Building'],
            [9, 'Painting & Polishing Work', 'PL1378-Bldg-9', 3565674.806, 'Building'],
            [10, 'Plumbing, Drainage Work', 'PL1378-Bldg-10', 2980422.693, 'Building'],
            [11, 'Electrical Work', 'PL1378-Bldg-11', 5906683.258, 'Building'],
            [12, 'Lift Work', 'PL1378-Bldg-12', 8247691.71, 'Building'],
            [13, 'Buildings Fire Fighting Work', 'PL1378-Bldg-13', 4736179.032, 'Building'],
            [14, 'Elevation, Glazing, Facade Work', 'PL1378-Bldg-14', 20245360.03, 'Building'],
            [15, 'Bldg Amenities-HVAC System,ID Lobby', 'PL1378-Bldg-15', 7662439.597, 'Building'],
            [16, 'Misc, Dep. Labour, Cleaning', 'PL1378-Bldg-16', 814989.8749, 'Building'],
            [17, 'Civil Infrastructure', 'PL1378-Bldg-17', 4736179.032, 'Building'],
            [18, 'Services Civil Infrastructure', 'PL1378-Bldg-18', 2024530.03, ' Development'],
            [19, 'Plumbing Services', 'PL1378-Bldg-19', 62439.597, 'Development'],
            [20, 'Electrical Services', 'PL1378-Bldg-20', 84989.8749, 'Development'],
            [21, 'Project Amenities', 'PL1378-Bldg-21', 46179.032, 'Development'],
            [22, 'Techanical Consultants', 'PL1378-Bldg-22', 2245360.03, 'Technical'],
            [23, 'Site overhead Expenses', 'PL1378-Bldg-23', 62439.597, ' Technical'], 
        ];
    }

    public function headings(): array
    {
        return [
            'Sr No',
            'Budget Head',
            'WBS',
            'Amount',
            'Type'
        ];
    }

    public function title(): string
    {
        return 'Budget Template';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Header row styling
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2E75B6'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ],
            // Style for Sr No column
            'A' => [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
            ],
            // Style for Amount column
            'D' => [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                ],
            ],
            // Style for Type column
            'E' => [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
            ],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2, // Format: 1,234,567.89
        ];
    }
}
