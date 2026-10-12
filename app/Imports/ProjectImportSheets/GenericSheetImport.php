<?php

namespace App\Imports\ProjectImportSheets;

use App\Imports\ProjectImport;
use App\Traits\MapsImportHeadings;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class GenericSheetImport implements ToCollection, WithHeadingRow
{
    use MapsImportHeadings;

    private ProjectImport $parent;

    private string $type;

    public function __construct(ProjectImport $parent, string $type)
    {
        $this->parent = $parent;
        $this->type = $type;
    }

    public function collection(Collection $rows): void
    {
        $mappedRows = $rows->map(function ($row) {
            return $this->mapArabicHeadings($row->toArray());
        })->toArray();

        $this->parent->addRows($this->type, $mappedRows);
    }
}
