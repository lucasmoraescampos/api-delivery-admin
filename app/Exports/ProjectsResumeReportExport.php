<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class ProjectsResumeReportExport implements FromCollection, WithHeadings, ShouldAutoSize, WithTitle
{
    /**
     * ProjectsResumeReportExport constructor.
     *
     * @param Collection $collection
     * @param float $percent
     */
    public function __construct(Collection $collection, float $percent)
    {
        $this->collection = $collection;

        $this->percent = $percent;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        ini_set('memory_limit', '-1');

        $data = collect();

        $this->early = 0;

        $this->late = 0;

        $this->on_time = 0;

        $this->total = 0;

        foreach ($this->collection as $collect) {

            $data->push([
                'date'      => $collect['date'],
                'early'     => "{$collect['early']['value']} ({$collect['early']['percent']}%)",
                'late'      => "{$collect['late']['value']} ({$collect['late']['percent']}%)",
                'on_time'   => "{$collect['on_time']['value']} ({$collect['on_time']['percent']}%)",
                'total'     => $collect['total'] . ' (100%)'
            ]);

            $this->early += $collect['early']['value'];

            $this->late += $collect['late']['value'];

            $this->on_time += $collect['on_time']['value'];

            $this->total += $collect['total'];

        }

        $data->push([
            'Total',
            $this->early,
            $this->late,
            $this->on_time,
            $this->total
        ]);

        $data->push([
            null,
            null,
            null,
            null,
            null            
        ]);

        $data->push([
            "{$this->percent}% of total projects.",
            null,
            null,
            null,
            null            
        ]);

        return $data;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Early',
            'Late',
            'On time',
            'Total'
        ];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Resume';
    }
}
