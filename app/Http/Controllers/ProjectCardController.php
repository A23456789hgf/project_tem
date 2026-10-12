<?php

namespace App\Http\Controllers;

use App\Exports\ProjectCardExport;
use App\Http\Controllers\Project\Services\ProjectService;
use App\Models\Project;
use App\Services\DocumentLetterheadService;
use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ProjectCardController extends Controller
{
    /**
     * Display the project card
     */
    /**
     * Display the project card
     */
    public function show(Project $project)
    {
        $this->loadCardRelations($project);

        return view('projects.card.show', compact('project'));
    }

    /**
     * Print the project card
     */
    public function print(Project $project)
    {
        $this->loadCardRelations($project);
        $letterheadData = app(DocumentLetterheadService::class)->resolveFor($project);
        $letterhead = $letterheadData->letterhead;

        return view('projects.card.print', compact('project', 'letterhead', 'letterheadData'));
    }

    /**
     * Helper to load all card relations
     */
    private function loadCardRelations(Project $project)
    {
        app(ProjectService::class)->authorizeProjectAccess($project);

        $project->load([
            'program',
            'domain',
            'subdomain',
            'intervention',
            'detail',
            'cost',
            'locations.governorate',
            'locations.directorate',
            'locations.subArea',
            'locations.village',
            'supervisingAuthorities.authority',
            'supervisingAuthorities.internalEntity',
            'implementingEntities.authority',
            'implementingEntities.internalEntity',
            'participatingEntities.authority',
            'participatingEntities.internalEntity',
            'beneficiaryEntities.authority',
            'beneficiaryEntities.internalEntity',
            'financings.fundingSource',
            'financings.authority',
            'financings.financingType',
            'risks',
            'mainObjectives',
            'specialObjectives.results.outputs',
            'resultOutputs',
            'preliminaryActivities.procedures.costs.financialItem',
            'preliminaryActivities.procedures.costs.unit',
            'executiveActivities.actions.assignedEntities',
            'executiveActivities.actions.costs.financialItem',
            'executiveActivities.actions.costs.unit',
            'documents',
        ]);
    }

    /**
     * Generate QR code URL for project
     */
    public function getQrCodeUrl(Project $project)
    {
        $projectUrl = route('projects.show', $project->id);

        // Using free QR code API service
        return 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data='.urlencode($projectUrl);
    }

    /**
     * Export project card as PDF
     */
    public function exportPdf(Project $project)
    {
        $this->loadCardRelations($project);
        $letterheadData = app(DocumentLetterheadService::class)->resolveFor($project);
        $letterhead = $letterheadData->letterhead;

        $fileName = 'project_card_'.$project->id.'.pdf';

        if (class_exists('\\Mpdf\\Mpdf')) {
            $tempDir = storage_path('app/tmp/mpdf');
            if (! file_exists($tempDir)) {
                @mkdir($tempDir, 0777, true);
            }

            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => 'P',
                'margin_left' => 10,
                'margin_right' => 10,
                'margin_top' => 10,
                'margin_bottom' => 10,
                'default_font' => 'dejavusans',
                'direction' => 'rtl',
                'autoScriptToLang' => true,
                'autoLangToFont' => true,
                'autoArabic' => true,
                'tempDir' => $tempDir,
            ]);

            $mpdf->SetDirectionality('rtl');
            $html = view('projects.card.pdf', compact('project', 'letterhead', 'letterheadData'))->render();
            $mpdf->WriteHTML($html);

            return response()->streamDownload(function () use ($mpdf) {
                echo $mpdf->Output('', 'S');
            }, $fileName, [
                'Content-Type' => 'application/pdf',
            ]);
        }

        // Fallback to DomPDF with Arabic font
        $pdf = \PDF::loadView('projects.card.pdf', compact('project', 'letterhead', 'letterheadData'));
        $pdf->setPaper('a4');
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isPhpEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'DejaVu Sans',
        ]);

        return $pdf->download($fileName);
    }

    /**
     * Export project card as Excel table
     */
    public function exportTable(Project $project)
    {
        $this->loadCardRelations($project);

        // Create the export instance
        $export = new ProjectCardExport($project);
        $spreadsheet = $export->export();

        // Generate Excel file
        $writer = new Xlsx($spreadsheet);

        // Create response
        $fileName = 'project_card_'.$project->id.'_'.now()->format('Y-m-d-H-i-s').'.xlsx';

        return response()->stream(
            function () use ($writer) {
                $writer->save('php://output');
            },
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Pragma' => 'public',
            ]
        );
    }

    public function printReviews(Project $project)
    {
        // تحميل العلاقات اللازمة للمراجعات وحركة المشروع
        $project->load([
            'projectApprovals.stage',
            'projectApprovals.entity',
            'projectApprovals.financialReviewer',
            'projectApprovals.technicalReviewer',
            'projectApprovals.reviewedByUser',
            'movementLogs.approvalStage',
            'movementLogs.user',
            'creatorEntity',
            'program',
            'domain',
        ]);

        return view('projects.card.print-reviews', compact('project'));
    }
}
