<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectFinancingTemplateExport
{
    public function download($fileName)
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $headings = [
            'اسم المشروع',
            'مصدر التمويل',
            'جهة التمويل',
            'نوع التمويل',
            'شكل التمويل',
            'الشكل الفرعي',
            'مبلغ التمويل',
            'النسبة المئوية',
        ];

        // Add Headings
        $col = 'A';
        foreach ($headings as $heading) {
            $sheet->setCellValue($col.'1', $heading);
            $col++;
        }

        // Add Sample Data - Row 1
        $dataRows = [
            [
                'مشروع تجريبي أ',
                'حكومي', // Funding Source
                'وزارة المالية', // Authority
                'منحة', // Type
                'نقدي', // Form
                'مباشر', // Sub Form
                '500000', // Amount
                '50', // Percentage
            ],
            [
                'مشروع تجريبي أ',
                'منظمة', // Funding Source
                'جهة مانحة', // Authority
                'منحة', // Type
                'نقدي', // Form
                'مباشر', // Sub Form
                '300000', // Amount
                '30', // Percentage
            ],
            [
                'مشروع تجريبي أ',
                'محلي', // Funding Source
                'السلطة المحلية', // Authority
                'مساهمة', // Type
                'عيني', // Form
                'معدات', // Sub Form
                '200000', // Amount
                '20', // Percentage
            ],
            [
                'مشروع تجريبي ب',
                'حكومي', // Funding Source
                'وزارة المالية', // Authority
                'قرض', // Type
                'نقدي', // Form
                'مباشر', // Sub Form
                '1000000', // Amount
                '100', // Percentage
            ],
        ];

        $rowIdx = 2;
        foreach ($dataRows as $data) {
            $col = 'A';
            foreach ($data as $val) {
                $sheet->setCellValue($col.$rowIdx, $val);
                $col++;
            }
            $rowIdx++;
        }

        // Style header
        $headerRange = 'A1:'.$sheet->getHighestColumn().'1';
        $sheet->getStyle($headerRange)->getFont()->setBold(true);

        $writer = new Xlsx($spreadsheet);

        return new StreamedResponse(
            function () use ($writer) {
                $writer->save('php://output');
            },
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
            ]
        );
    }
}
