<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class ProjectsDetailsReportExport implements FromCollection, WithHeadings, ShouldAutoSize, WithTitle
{
    private $collection;

    /**
     * ProjectsDetailsReportExport constructor.
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
        return [
            'Date',
            'Project',
            'Delivery window',
            'Driver',
            'Stop number',
            'Started at',
            'Arrived at / Skipped at',
            'In window',
            'Order id',
            'Status',
            'Stop',
            'Phone',
            'Address',
            'Distance',
            'Duration'
        ];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Details';
    }
}
