<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Log;

class SmsEventManager
{
    /**
     * Get the full SMS Event Registry from configuration.
     */
    public function getRegistry(): array
    {
        return config('sms_events', []);
    }

    /**
     * Find metadata for a specific event key.
     */
    public function findEventMeta(string $eventKey): ?array
    {
        $registry = $this->getRegistry();

        foreach ($registry as $moduleKey => $module) {
            if (isset($module['events'][$eventKey])) {
                $meta = $module['events'][$eventKey];
                $meta['module_key'] = $moduleKey;
                $meta['module_label'] = $module['label'] ?? $moduleKey;

                return $meta;
            }
        }

        return null;
    }

    /**
     * Check if an SMS event is enabled.
     */
    public function enabled(string $eventKey): bool
    {
        $meta = $this->findEventMeta($eventKey);

        // Unknown Event Safety: return false if event key is not registered.
        if (! $meta) {
            Log::warning("Unknown SMS event key checked: {$eventKey}");

            return false;
        }

        // Required Events (e.g. OTP) are permanently enabled.
        if (! empty($meta['required'])) {
            return true;
        }

        $settingKey = $this->getSettingKey($eventKey);
        $dbValue = SystemSetting::get($settingKey);

        if ($dbValue !== null) {
            return $dbValue === '1';
        }

        // Legacy Settings Fallback (Idempotent initial read)
        $legacyValue = $this->getLegacyFallback($eventKey);
        if ($legacyValue !== null) {
            return $legacyValue === '1';
        }

        return (bool) ($meta['default'] ?? false);
    }

    /**
     * Update status for a specific event key.
     */
    public function updateEventStatus(string $eventKey, bool $enabled): bool
    {
        $meta = $this->findEventMeta($eventKey);

        if (! $meta) {
            return false;
        }

        // Backend guard: Required events cannot be disabled.
        if (! empty($meta['required'])) {
            return false;
        }

        $settingKey = $this->getSettingKey($eventKey);
        SystemSetting::set($settingKey, $enabled ? '1' : '0', 'sms');

        return true;
    }

    /**
     * Bulk update event statuses from Settings form input.
     */
    public function updateSettings(array $statuses): void
    {
        $registry = $this->getRegistry();

        foreach ($registry as $module) {
            foreach ($module['events'] as $eventKey => $meta) {
                if (! empty($meta['required'])) {
                    continue;
                }

                $isEnabled = ! empty($statuses[$eventKey]);
                $this->updateEventStatus($eventKey, $isEnabled);
            }
        }
    }

    /**
     * One-time safe & idempotent migration of legacy settings.
     */
    public function migrateLegacySettings(): void
    {
        // 1. Migrate user.registered
        $legacyUserReg = SystemSetting::get('sms_event_user_registered');
        $newUserRegKey = $this->getSettingKey('user.registered');
        if ($legacyUserReg !== null && SystemSetting::get($newUserRegKey) === null) {
            SystemSetting::set($newUserRegKey, $legacyUserReg, 'sms');
        }

        // 2. Migrate project.approved & project.submitted
        $legacyProjApproved = SystemSetting::get('sms_event_project_approved');
        if ($legacyProjApproved !== null) {
            $newProjApprovedKey = $this->getSettingKey('project.approved');
            $newProjSubmittedKey = $this->getSettingKey('project.submitted');

            if (SystemSetting::get($newProjApprovedKey) === null) {
                SystemSetting::set($newProjApprovedKey, $legacyProjApproved, 'sms');
            }

            if (SystemSetting::get($newProjSubmittedKey) === null) {
                SystemSetting::set($newProjSubmittedKey, $legacyProjApproved, 'sms');
            }
        }
    }

    /**
     * Convert event key to setting key format.
     */
    public function getSettingKey(string $eventKey): string
    {
        return 'sms_event_'.str_replace('.', '_', $eventKey);
    }

    /**
     * Safe legacy setting fallback reader.
     */
    private function getLegacyFallback(string $eventKey): ?string
    {
        if (in_array($eventKey, ['project.approved', 'project.submitted'], true)) {
            return SystemSetting::get('sms_event_project_approved');
        }

        if ($eventKey === 'user.registered') {
            return SystemSetting::get('sms_event_user_registered');
        }

        return null;
    }
}
