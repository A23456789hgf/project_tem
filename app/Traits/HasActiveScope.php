<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasActiveScope
{
    /**
     * Boot the trait and apply the global active scope.
     */
    public static function bootHasActiveScope()
    {
        static::addGlobalScope('active_only', function (Builder $builder) {
            $table = $builder->getModel()->getTable();

            // Determine column name (is_active or status)
            $column = 'is_active';

            // Special case for models that use 'status' instead of 'is_active'
            // If the model has a status column and is set to 'Active'
            if (property_exists($builder->getModel(), 'statusColumn')) {
                $column = $builder->getModel()->statusColumn;
            }

            if ($column === 'status') {
                $builder->whereIn($table.'.status', ['Active', 'active']);
            } else {
                $builder->where($table.'.is_active', true);
            }
        });
    }

    /**
     * Scope to bypass the global active filter.
     */
    public function scopeWithInactive($query)
    {
        return $query->withoutGlobalScope('active_only');
    }
}
