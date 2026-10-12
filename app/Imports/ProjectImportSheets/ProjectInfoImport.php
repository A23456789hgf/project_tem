<?php

namespace App\Imports\ProjectImportSheets;

use App\Imports\ProjectImport;
use App\Traits\MapsImportHeadings;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProjectInfoImport implements ToCollection, WithHeadingRow
{
    use MapsImportHeadings;

    private ProjectImport $parent;

    public function __construct(ProjectImport $parent)
    {
        $this->parent = $parent;
    }

    public function collection(Collection $rows): void
    {
        $originalRows = $rows->map(fn ($row) => $row->toArray())->toArray();
        $mappedRows = $rows->map(function ($row) {
            return $this->mapArabicHeadings($row->toArray());
        })->toArray();

        $this->parent->addRows('project_info', $mappedRows, $originalRows);
    }
}
