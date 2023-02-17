<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ProjectsReportExport implements WithMultipleSheets
{
    use Exportable;

    /**
     * ProjectsReportExport constructor.
     *
     * @param Collection $details
     * @param Collection $resume
     * @param float $percent
     */
    public function __construct(Collection $resume, Collection $details, float $percent)
    {
        $this->details = $details;

        $this->resume = $resume;

        $this->percent = $percent;
    }

    /**
     * @return array
     */
    public function sheets(): array
    {
        return [
            new ProjectsResumeReportExport($this->resume, $this->percent),
            new ProjectsDetailsReportExport($this->details)
        ];
    }
}
