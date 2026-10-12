<?php

namespace App\Traits;

use App\Models\ProjectActivityHistory;
use App\Services\FinancialReview\WorkflowActorContext;

trait HandlesItemNotesAndHistory
{
    /**
     * Process item notes from request and save them as history records.
     */
    protected function processItemNotes($request, $project, $reviewType, $approval = null)
    {
        if (! $request->has('item_notes') || ! is_array($request->item_notes)) {
            return;
        }

        foreach ($request->item_notes as $modelType => $items) {
            foreach ($items as $itemId => $noteText) {
                if (! empty(trim($noteText))) {
                    $metadata = [
                        'related_model' => $modelType,
                        'related_id' => $itemId,
                        'review_type' => $reviewType,
                    ];

                    if ($approval) {
                        $metadata['entity_id'] = $approval->entity_id ?? $approval->authority_id ?? null;
                        $metadata['entity_name'] = $approval->entity?->name
                            ?? $approval->authority?->agency_name
                            ?? $approval->authority?->name
                            ?? null;
                        $metadata['stage_code'] = $approval->drop
                            ?? $approval->stage_key
                            ?? $approval->stage?->code
                            ?? $approval->stage?->name
                            ?? null;
                    }

                    if ($actor = WorkflowActorContext::get()) {
                        $metadata = array_merge($metadata, array_filter($actor));
                    }

                    ProjectActivityHistory::create([
                        'project_id' => $project->id,
                        'user_id' => auth()->id(),
                        'action_type' => $reviewType.'_item_note',
                        'notes' => $noteText,
                        'metadata' => $metadata,
                    ]);
                }
            }
        }
    }

    /**
     * Log model changes if it is dirty.
     */
    protected function logModelChangesIfDirty($model, $project, $reviewType, $modelName, $approval = null)
    {
        if ($model->isDirty()) {
            $changes = [];
            foreach ($model->getDirty() as $field => $newValue) {
                $changes[$field] = [
                    'old' => $model->getOriginal($field),
                    'new' => $newValue,
                ];
            }

            $model->save();

            if (! empty($changes)) {
                $metadata = [
                    'related_model' => class_basename($model),
                    'related_id' => $model->id,
                    'review_type' => $reviewType,
                    'changes' => $changes,
                ];

                if ($approval) {
                    $metadata['entity_id'] = $approval->entity_id ?? $approval->authority_id ?? null;
                    $metadata['entity_name'] = $approval->entity?->name
                        ?? $approval->authority?->agency_name
                        ?? $approval->authority?->name
                        ?? null;
                    $metadata['stage_code'] = $approval->drop
                        ?? $approval->stage_key
                        ?? $approval->stage?->code
                        ?? $approval->stage?->name
                        ?? null;
                }

                if ($actor = WorkflowActorContext::get()) {
                    $metadata = array_merge($metadata, array_filter($actor));
                }

                ProjectActivityHistory::create([
                    'project_id' => $project->id,
                    'user_id' => auth()->id(),
                    'action_type' => $reviewType.'_item_modification',
                    'notes' => 'تم تعديل البيانات خلال '.($reviewType == 'technical_review' ? 'المراجعة الفنية' : 'المراجعة المالية'),
                    'metadata' => $metadata,
                ]);
            }

            return true;
        }

        return false;
    }
}
