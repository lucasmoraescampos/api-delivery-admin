<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StopsSolutionExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    private $collection;
    
    /**
     * StopsSolutionExport constructor.
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
        return ['driver', 'step', 'order_id', 'name', 'address', 'phone', 'status', 'bags', 'note', 'arrived_at', 'skipped_at'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            'A'     => ['alignment' => ['horizontal' => 'left']],
            'B'     => ['alignment' => ['horizontal' => 'left']],
            'C'     => ['alignment' => ['horizontal' => 'left']],
            'D'     => ['alignment' => ['horizontal' => 'left']],
            'E'     => ['alignment' => ['horizontal' => 'left']],
            'F'     => ['alignment' => ['horizontal' => 'left']],
            'G'     => ['alignment' => ['horizontal' => 'left']],
            'H'     => ['alignment' => ['horizontal' => 'left']],
            'I'     => ['alignment' => ['horizontal' => 'left']],
            'J'     => ['alignment' => ['horizontal' => 'left']],
            'K'     => ['alignment' => ['horizontal' => 'left']],
            'A1'    => ['font'      => ['bold' => true]],
            'B1'    => ['font'      => ['bold' => true]],
            'C1'    => ['font'      => ['bold' => true]],
            'D1'    => ['font'      => ['bold' => true]],
            'E1'    => ['font'      => ['bold' => true]],
            'F1'    => ['font'      => ['bold' => true]],
            'G1'    => ['font'      => ['bold' => true]],
            'H1'    => ['font'      => ['bold' => true]],
            'I1'    => ['font'      => ['bold' => true]],
            'J1'    => ['font'      => ['bold' => true]],
            'K1'    => ['font'      => ['bold' => true]]
        ];
    }
}
