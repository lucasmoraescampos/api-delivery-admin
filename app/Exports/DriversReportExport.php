<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class DriversReportExport implements WithMultipleSheets
{
    use Exportable;
    
    /**
     * DriversReportExport constructor.
     *
     * @param Collection $resume
     * @param Collection $details
     */
    public function __construct(Collection $resume, Collection $details)
    {
        $this->resume = $resume;

        $this->details = $details;
    }

    /**
     * @return array
     */
    public function sheets(): array
    {
        return [
            new DriversResumeReportExport($this->resume),
            new DriversDetailsReportExport($this->details)
        ];
    }
}
