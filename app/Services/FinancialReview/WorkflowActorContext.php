<?php

namespace App\Services\FinancialReview;

class WorkflowActorContext
{
    protected static ?array $currentContext = null;

    /**
     * Set the current integration actor context.
     */
    public static function set(string $source = 'erpnext', ?string $externalUserId = null, ?string $externalUserName = null): void
    {
        self::$currentContext = [
            'source' => $source,
            'external_user_id' => $externalUserId,
            'external_user_name' => $externalUserName,
        ];
    }

    /**
     * Get the current actor context.
     */
    public static function get(): ?array
    {
        return self::$currentContext;
    }

    /**
     * Clear the current context.
     */
    public static function clear(): void
    {
        self::$currentContext = null;
    }
}
