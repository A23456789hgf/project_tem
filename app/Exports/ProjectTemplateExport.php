<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ProjectTemplateExport implements Export, WithMultipleSheets
{
    protected $projects;

    public function __construct($projects = null)
    {
        // Ensure $projects is a collection
        if ($projects && ! ($projects instanceof Collection)) {
            $projects = collect([$projects]);
        }
        $this->projects = $projects;
    }

    public function sheets(): array
    {
        return [
            new Sheets\ProjectInfoSheet($this->projects),
            new Sheets\ProjectDetailSheet($this->projects),
            new Sheets\LocationsSheet($this->projects),
            new Sheets\MainObjectivesSheet($this->projects),
            new Sheets\SpecialObjectivesSheet($this->projects),
            new Sheets\ResultsOutputsSheet($this->projects),
            new Sheets\PreliminaryActivitiesSheet($this->projects),
            new Sheets\ExecutiveActivitiesSheet($this->projects),
            new Sheets\FinancingsSheet($this->projects),
            new Sheets\EntitiesSheet($this->projects),
            new Sheets\LookupSheet,
        ];
    }
}
