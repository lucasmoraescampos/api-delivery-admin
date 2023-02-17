<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StopsSummaryExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    private $collection;
    
    /**
     * StopsSummaryExport constructor.
     *
     * @param Collection $collection
     */
    public function __construct(Collection $collection)
    {
        $this->collection = $collection;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        ini_set('memory_limit', '-1');

        return $this->collection;
    }

    public function headings(): array
    {
        return ['order_id', 'name', 'address', 'phone'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            'A'     => ['alignment' => ['horizontal' => 'left']],
            'B'     => ['alignment' => ['horizontal' => 'left']],
            'C'     => ['alignment' => ['horizontal' => 'left']],
            'D'     => ['alignment' => ['horizontal' => 'left']],
            'A1'    => ['font'      => ['bold' => true]],
            'B1'    => ['font'      => ['bold' => true]],
            'C1'    => ['font'      => ['bold' => true]],
            'D1'    => ['font'      => ['bold' => true]]
        ];
    }
}
