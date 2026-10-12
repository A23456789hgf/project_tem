<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Multi-sheet Excel export for projects skipped due to missing dropdown values.
 *
 * Sheet 1: Summary of each missing value (project name, row, field, value)
 * Sheet 2: Full original rows of skipped projects, preserving Excel structure
 */
class DropdownMissingValuesExport implements Export, WithMultipleSheets
{
    use Exportable;

    public function __construct(
        private readonly array $dropdownSkipped,
        private readonly array $originalHeadings = []
    ) {}

    public function sheets(): array
    {
        return [
            new DropdownMissingSheets\MissingSummarySheet($this->dropdownSkipped),
            new DropdownMissingSheets\SkippedProjectsSheet($this->dropdownSkipped, $this->originalHeadings),
        ];
    }
}
