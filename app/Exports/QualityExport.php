<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Layout;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QualityExport
{
    public static function export(array $reportData)
    {
        $spreadsheet = new Spreadsheet;

        // Sheet 1: Summary & KPIs
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('ملخص الجودة');
        $sheet->setRightToLeft(true);
        self::buildSummarySheet($sheet, $reportData);

        // Sheet 2: Projects
        $projectsSheet = $spreadsheet->createSheet();
        $projectsSheet->setTitle('المشاريع');
        $projectsSheet->setRightToLeft(true);
        self::buildProjectsSheet($projectsSheet, $reportData['projects_data']);

        // Set Writer to include charts
        $writer = new Xlsx($spreadsheet);
        $writer->setIncludeCharts(true);

        $fileName = 'Quality_Report_'.date('Y_m_d_His').'.xlsx';

        $response = new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="'.$fileName.'"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    private static function buildSummarySheet($sheet, array $reportData)
    {
        $kpis = $reportData['kpis'];

        $sheet->setCellValue('A1', 'تقرير الجودة الشامل');
        $sheet->mergeCells('A1:B1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);

        $sheet->setCellValue('A3', 'إجمالي المشاريع');
        $sheet->setCellValue('B3', $kpis['total_projects']);

        $sheet->setCellValue('A4', 'المشاريع المكتملة');
        $sheet->setCellValue('B4', $kpis['completed_projects']);

        $sheet->setCellValue('A5', 'متوسط الجودة');
        $sheet->setCellValue('B5', $kpis['average_score'].'%');

        // Distribution Data for Chart
        $sheet->setCellValue('D1', 'التوزيع');
        $sheet->setCellValue('E1', 'العدد');
        $row = 2;
        foreach ($reportData['charts']['quality_distribution']['labels'] as $i => $label) {
            $sheet->setCellValue('D'.$row, $label);
            $sheet->setCellValue('E'.$row, $reportData['charts']['quality_distribution']['data'][$i]);
            $row++;
        }

        // Create Chart
        $dataSeriesLabels = [
            new DataSeriesValues('String', 'ملخص الجودة!$E$1', null, 1),
        ];
        $xAxisTickValues = [
            new DataSeriesValues('String', 'ملخص الجودة!$D$2:$D$6', null, 5),
        ];
        $dataSeriesValues = [
            new DataSeriesValues('Number', 'ملخص الجودة!$E$2:$E$6', null, 5),
        ];

        $series = new DataSeries(
            DataSeries::TYPE_PIECHART,
            null,
            range(0, count($dataSeriesValues) - 1),
            $dataSeriesLabels,
            $xAxisTickValues,
            $dataSeriesValues
        );

        $layout = new Layout;
        $layout->setShowVal(true);

        $plotArea = new PlotArea($layout, [$series]);
        $legend = new Legend(Legend::POSITION_RIGHT, null, false);
        $title = new Title('توزيع الجودة');

        $chart = new Chart(
            'chart1',
            $title,
            $legend,
            $plotArea,
            true,
            0,
            null,
            null
        );

        $chart->setTopLeftPosition('A8');
        $chart->setBottomRightPosition('F20');

        $sheet->addChart($chart);

        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private static function buildProjectsSheet($sheet, array $projectsData)
    {
        $headers = ['المشروع', 'الجهة', 'الموقع', 'الحالة', 'جودة الوقت %', 'الجودة المالية %', 'التقييم العام %'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col.'1', $header);
            $sheet->getStyle($col.'1')->getFont()->setBold(true);
            $col++;
        }

        $row = 2;
        foreach ($projectsData as $data) {
            $sheet->setCellValue('A'.$row, $data['project']->project_name);
            $sheet->setCellValue('B'.$row, $data['entity_name']);
            $sheet->setCellValue('C'.$row, $data['location_name']);
            $sheet->setCellValue('D'.$row, $data['status_name']);
            $sheet->setCellValue('E'.$row, $data['statistics']['time_positive_percentage']);
            $sheet->setCellValue('F'.$row, $data['statistics']['financial_positive_percentage']);
            $sheet->setCellValue('G'.$row, $data['statistics']['overall_quality_score']);
            $row++;
        }

        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:G'.($row - 1));

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Add Bar Chart for Project Scores
        if (count($projectsData) > 0) {
            $dataSeriesLabels = [
                new DataSeriesValues('String', 'المشاريع!$G$1', null, 1),
            ];
            $xAxisTickValues = [
                new DataSeriesValues('String', 'المشاريع!$A$2:$A$'.($row - 1), null, count($projectsData)),
            ];
            $dataSeriesValues = [
                new DataSeriesValues('Number', 'المشاريع!$G$2:$G$'.($row - 1), null, count($projectsData)),
            ];

            $series = new DataSeries(
                DataSeries::TYPE_BARCHART,
                DataSeries::GROUPING_STANDARD,
                range(0, count($dataSeriesValues) - 1),
                $dataSeriesLabels,
                $xAxisTickValues,
                $dataSeriesValues
            );

            $layout = new Layout;
            $plotArea = new PlotArea($layout, [$series]);
            $legend = new Legend(Legend::POSITION_RIGHT, null, false);
            $title = new Title('جودة المشاريع');

            $chart = new Chart(
                'chart2',
                $title,
                $legend,
                $plotArea,
                true,
                0,
                null,
                null
            );

            $chart->setTopLeftPosition('I2');
            $chart->setBottomRightPosition('Q20');
            $sheet->addChart($chart);
        }
    }
}
