<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProjectEntitySelectionValidator
{
    /**
     * @param  array<int|string, array<string, mixed>>  $entities
     */
    public static function validate(array $entities, string $attribute): void
    {
        foreach ($entities as $index => $entity) {
            if (is_array($entity) && ! isset($entity['authority_type']) && isset($entity['entity_type'])) {
                $entities[$index]['authority_type'] = $entity['entity_type'];
            }
        }

        $validator = Validator::make($entities, [
            '*.authority_type' => ['required', Rule::in(['internal', 'external'])],
            '*.internal_entity_id' => ['nullable', 'integer', 'exists:internal_entities,id'],
            '*.authority_id' => ['nullable', 'integer', 'exists:authorities,id'],
            '*.parent_id' => ['nullable', 'integer'],
        ]);

        $validator->after(function ($validator) use ($entities): void {
            foreach ($entities as $index => $entity) {
                if (! is_array($entity)) {
                    continue;
                }

                $type = $entity['authority_type'] ?? $entity['entity_type'] ?? null;
                if (! in_array($type, ['internal', 'external'], true)) {
                    continue;
                }

                $selectedField = $type === 'internal' ? 'internal_entity_id' : 'authority_id';
                $otherField = $type === 'internal' ? 'authority_id' : 'internal_entity_id';

                if (empty($entity[$selectedField])) {
                    $validator->errors()->add(
                        "{$index}.{$selectedField}",
                        'A selected entity is required for the chosen entity type.'
                    );
                }

                if (! empty($entity[$otherField])) {
                    $validator->errors()->add(
                        "{$index}.{$otherField}",
                        'The entity ID must match the selected entity type.'
                    );
                }
            }
        });

        if ($validator->fails()) {
            $errors = [];
            foreach ($validator->errors()->messages() as $key => $messages) {
                $errors["{$attribute}.{$key}"] = $messages;
            }

            throw ValidationException::withMessages($errors);
        }
    }
}
