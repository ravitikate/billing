<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class BillDelayDetailsExport implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    public function __construct(
        private array $rows,
        private array $columns,
        private string $sheetTitle = 'Delay Details'
    ) {
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->columns;
    }

    public function title(): string
    {
        return $this->sheetTitle;
    }
}
