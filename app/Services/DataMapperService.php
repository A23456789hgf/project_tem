<?php

namespace App\Services;

use App\Models\Project;

class DataMapperService
{
    protected FrappeAPIService $frappeService;

    public function __construct(?FrappeAPIService $frappeService = null)
    {
        $this->frappeService = $frappeService ?? app(FrappeAPIService::class);
    }

    /**
     * Map ERPNext Project data to Laravel Project attributes
     */
    public function mapErpNextProjectToLaravel(array $erpData): array
    {
        $mapped = [];

        if (isset($erpData['name'])) {
            $mapped['erpnext_project_id'] = $erpData['name'];
        }

        if (isset($erpData['project_name'])) {
            $mapped['project_name'] = $erpData['project_name'];
        }

        if (isset($erpData['expected_start_date'])) {
            $mapped['start_date_gregorian'] = $erpData['expected_start_date'];
        }

        if (isset($erpData['expected_end_date'])) {
            $mapped['end_date_gregorian'] = $erpData['expected_end_date'];
        }

        if (isset($erpData['status'])) {
            $mappedStatus = $this->mapStatusToLaravel($erpData['status']);
            if ($mappedStatus) {
                $mapped['status'] = $mappedStatus;
            }
        }

        if (! empty($erpData['company'])) {
            $entity = $this->frappeService->resolveEntityFromErpNextCompany($erpData['company']);
            if ($entity) {
                $mapped['creator_entity_id'] = $entity->id;
                $mapped['internal_entity_id'] = $entity->id;
                $mapped['created_by_entity'] = $entity->name;
            }
        }

        return $mapped;
    }

    /**
     * Map Laravel Project status from ERPNext status
     * Returns null if unknown, so we preserve the existing Laravel status.
     */
    private function mapStatusToLaravel(string $erpStatus): ?string
    {
        $statusMap = [
            'Draft' => 'draft',
            'Open' => 'approved',
            'In Progress' => 'in_execution',
            'Completed' => 'completed',
            'Cancelled' => 'cancelled',
            'Closed' => 'closed',
        ];

        return $statusMap[$erpStatus] ?? null;
    }
}
