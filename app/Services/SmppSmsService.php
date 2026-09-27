<?php

namespace App\Services;

use App\Jobs\SendSmsJob;
use App\Models\SmsLog;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmppSmsService
{
    private ?string $authenticationFailureReason = null;

    /**
     * Normalize phone number by stripping whitespace and non-numeric/plus characters.
     */
    public function normalizePhoneNumber(string $phone): string
    {
        return preg_replace('/[^0-9+]/', '', trim($phone));
    }

    /**
     * Build login endpoint URL.
     */
    private function loginUrl(): string
    {
        $originalUrl = (string) config('services.smpp_sms.login_url');
        if ($originalUrl !== '') {
            return $originalUrl;
        }

        $explicit = (string) config('sms.login_url');
        if ($explicit !== '') {
            return $explicit;
        }

        return rtrim((string) config('sms.api_url'), '/').'/login';
    }

    /**
     * Build send endpoint URL.
     */
    private function sendUrl(): string
    {
        $originalUrl = (string) config('services.smpp_sms.send_url');
        if ($originalUrl !== '') {
            return $originalUrl;
        }

        $explicit = (string) config('sms.send_url');
        if ($explicit !== '') {
            return $explicit;
        }

        return rtrim((string) config('sms.api_url'), '/').'/send-sms';
    }

    /**
     * Authenticate with the SMS Gateway and cache the token.
     */
    public function login(): ?string
    {
        $this->authenticationFailureReason = null;

        $cacheKey = 'smpp_sms_token';
        $cachedToken = Cache::get($cacheKey);

        if (! empty($cachedToken)) {
            return $cachedToken;
        }

        $loginUrl = $this->loginUrl();

        if (empty($loginUrl)) {
            $this->authenticationFailureReason = 'SMPP login URL is not configured';
            Log::error('SMPP login URL is not configured.');

            return null;
        }

        try {
            $response = Http::withOptions([
                'verify' => (bool) config('sms.verify_ssl', false),
                'timeout' => (int) config('sms.timeout', 15),
                'connect_timeout' => (int) config('sms.connect_timeout', 5),
            ])->acceptJson()->post($loginUrl, [
                'email' => $this->email(),
                'password' => $this->password(),
            ]);

            if ($response->failed()) {
                $payload = $response->json();
                $payload = is_array($payload) ? $payload : ['message' => $response->body()];
                $this->authenticationFailureReason = $this->extractProviderReason(
                    $payload,
                    'SMPP authentication failed (HTTP '.$response->status().')'
                );

                Log::error('SMPP login failed.', [
                    'status' => $response->status(),
                    'error' => $payload,
                ]);

                return null;
            }

            $data = $response->json() ?? [];
            $token = $data['token'] ?? $data['access_token'] ?? null;

            if (empty($token)) {
                $this->authenticationFailureReason = 'SMPP login response does not contain a token';
                Log::warning('SMPP login succeeded but token is missing from response.');

                return null;
            }

            $ttlSeconds = (int) ($data['expires_in'] ?? config('sms.token_ttl', 3300));
            Cache::put($cacheKey, $token, max(60, $ttlSeconds));

            return $token;
        } catch (ConnectionException $e) {
            $this->authenticationFailureReason = 'SMS authentication service is unreachable: '.$e->getMessage();
            Log::error('SMPP login timed out or unreachable.', ['error' => $e->getMessage()]);

            return null;
        } catch (\Throwable $e) {
            $this->authenticationFailureReason = 'SMS authentication failed: '.$e->getMessage();
            Log::error('SMPP login unexpected error.', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Main Synchronous SMS send method with DB logging.
     */
    public function sendSMS(?int $userId, string $mobileNo, string $message, ?string $eventName = null, ?int $sentByUserId = null): array
    {
        $cleanPhone = $this->normalizePhoneNumber($mobileNo);
        $senderId = $sentByUserId ?? Auth::id();

        if (empty($cleanPhone)) {
            return $this->logSms($userId, $mobileNo, $message, 'failed', null, 'Phone number is empty', $eventName, $senderId);
        }

        $token = $this->login();

        if (empty($token)) {
            return $this->logSms(
                $userId,
                $cleanPhone,
                $message,
                'failed',
                null,
                $this->authenticationFailureReason ?? 'SMS authentication failed (token not found)',
                $eventName,
                $senderId
            );
        }

        $sendUrl = $this->sendUrl();

        if (empty($sendUrl)) {
            return $this->logSms($userId, $cleanPhone, $message, 'failed', null, 'SMPP send URL is not configured', $eventName, $senderId);
        }

        $maxRetries = max(0, (int) config('sms.retries', 2));
        $retryDelayMs = max(0, (int) config('sms.retry_delay_ms', 700));
        $lastResult = null;

        for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
            $result = $this->sendWithToken($token, $userId, $cleanPhone, $message);
            $result['attempt'] = $attempt + 1;
            $lastResult = $result;

            if (! empty($result['ok'])) {
                return $this->logSms(
                    $userId,
                    $cleanPhone,
                    $message,
                    'sent',
                    $result['provider_response'] ?? null,
                    null,
                    $eventName,
                    $senderId
                );
            }

            $isLastAttempt = $attempt >= $maxRetries;
            if ($isLastAttempt || ! $this->isRetryableFailure($result)) {
                return $this->logSms(
                    $userId,
                    $cleanPhone,
                    $message,
                    'failed',
                    $result['error'] ?? null,
                    $result['reason'] ?? 'SMS send failed',
                    $eventName,
                    $senderId
                );
            }

            if ($retryDelayMs > 0) {
                usleep($retryDelayMs * 1000);
            }
        }

        return $this->logSms(
            $userId,
            $cleanPhone,
            $message,
            'failed',
            $lastResult['error'] ?? null,
            $lastResult['reason'] ?? 'SMS send failed after retries',
            $eventName,
            $senderId
        );
    }

    /**
     * Backward-compatible synchronous wrapper.
     */
    public function sendSmsSync(?int $userId, string $mobileNo, string $message, ?string $eventName = null, ?int $sentByUserId = null): array
    {
        return $this->sendSMS($userId, $mobileNo, $message, $eventName, $sentByUserId);
    }

    /**
     * Dispatch SMS via queue (asynchronous).
     */
    public function sendSmsAsync(?int $userId, string $mobileNo, string $message, ?string $eventName = null, ?int $sentByUserId = null): void
    {
        SendSmsJob::dispatch($userId, $mobileNo, $message, $eventName, $sentByUserId ?? Auth::id());
    }

    /**
     * Send SMS to multiple users/recipients.
     */
    public function sendToMultiple($users, string $message, ?string $eventName = null, bool $async = false): array
    {
        $results = [];

        foreach ($users as $user) {
            $phone = is_object($user) ? ($user->phone ?? null) : ($user['phone'] ?? null);
            $userId = is_object($user) ? ($user->id ?? null) : ($user['id'] ?? null);

            if (empty($phone)) {
                $results[] = $this->logSms($userId, 'N/A', $message, 'failed', null, 'User has no phone number', $eventName);

                continue;
            }

            try {
                if ($async) {
                    $this->sendSmsAsync($userId, $phone, $message, $eventName);
                    $results[] = [
                        'ok' => true,
                        'status' => 'queued',
                        'userId' => $userId,
                        'phone' => $phone,
                    ];
                } else {
                    $results[] = $this->sendSMS($userId, $phone, $message, $eventName);
                }
            } catch (\Throwable $e) {
                Log::error("Failed to send SMS to user {$userId}: ".$e->getMessage());
                $results[] = [
                    'ok' => false,
                    'status' => 'failed',
                    'userId' => $userId,
                    'phone' => $phone,
                    'reason' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    private function sendWithToken(string $token, ?int $userId, string $mobileNo, string $message): array
    {
        $sendUrl = $this->sendUrl();

        try {
            $response = Http::withOptions([
                'verify' => (bool) config('sms.verify_ssl', false),
                'timeout' => (int) config('sms.timeout', 15),
                'connect_timeout' => (int) config('sms.connect_timeout', 5),
            ])->withToken($token)
                ->acceptJson()
                ->post($sendUrl, [
                    'message' => $message,
                    'phone' => $mobileNo,
                ]);

            $decoded = $response->json();

            if ($response->successful()) {
                return [
                    'ok' => true,
                    'userId' => $userId,
                    'phone' => $mobileNo,
                    'provider_response' => $decoded,
                ];
            }

            $statusCode = $response->status();
            $payload = is_array($decoded) ? $decoded : ['message' => $response->body()];
            $reason = $this->extractProviderReason($payload, 'HTTP Error '.$statusCode);

            if (in_array($statusCode, [401, 403], true)) {
                Cache::forget('smpp_sms_token');
            }

            $retryableStatuses = [408, 425, 429, 500, 502, 503, 504];
            $retryable = in_array($statusCode, $retryableStatuses, true);

            Log::warning('SMPP send failed.', [
                'userId' => $userId,
                'phone' => $mobileNo,
                'status' => $statusCode,
                'error' => $payload,
            ]);

            return [
                'ok' => false,
                'userId' => $userId,
                'phone' => $mobileNo,
                'error' => $payload,
                'status_code' => $statusCode,
                'reason' => $reason,
                'retryable' => $retryable,
            ];
        } catch (ConnectionException $e) {
            $reason = 'SMS service unreachable (timeout): '.$e->getMessage();

            Log::warning('SMPP send timed out or unreachable.', [
                'userId' => $userId,
                'phone' => $mobileNo,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'userId' => $userId,
                'phone' => $mobileNo,
                'error' => ['message' => $reason],
                'reason' => $reason,
                'retryable' => true,
            ];
        } catch (\Throwable $e) {
            $reason = $e->getMessage();
            $retryable = (bool) preg_match('/connection closed|timed out|timeout|cURL error 28/i', $reason);

            Log::error('SMPP send unexpected error.', [
                'userId' => $userId,
                'phone' => $mobileNo,
                'error' => $reason,
            ]);

            return [
                'ok' => false,
                'userId' => $userId,
                'phone' => $mobileNo,
                'error' => ['message' => $reason],
                'reason' => $reason,
                'retryable' => $retryable,
            ];
        }
    }

    private function isRetryableFailure(array $result): bool
    {
        return ! empty($result['retryable']);
    }

    private function email(): string
    {
        return (string) (config('services.smpp_sms.email') ?: config('sms.email'));
    }

    private function password(): string
    {
        return (string) (config('services.smpp_sms.password') ?: config('sms.password'));
    }

    private function extractProviderReason(array $payload, string $fallback): string
    {
        $candidateKeys = ['message', 'error', 'detail', 'description', 'msg'];

        foreach ($candidateKeys as $key) {
            if (isset($payload[$key]) && is_string($payload[$key]) && trim($payload[$key]) !== '') {
                return trim($payload[$key]);
            }
        }

        if (isset($payload['errors'])) {
            if (is_array($payload['errors'])) {
                foreach ($payload['errors'] as $errorItem) {
                    if (is_string($errorItem) && trim($errorItem) !== '') {
                        return trim($errorItem);
                    }

                    if (is_array($errorItem)) {
                        foreach ($candidateKeys as $key) {
                            if (isset($errorItem[$key]) && is_string($errorItem[$key]) && trim($errorItem[$key]) !== '') {
                                return trim($errorItem[$key]);
                            }
                        }
                    }
                }
            } elseif (is_string($payload['errors']) && trim($payload['errors']) !== '') {
                return trim($payload['errors']);
            }
        }

        return trim($fallback) !== '' ? $fallback : 'Unknown SMS provider error';
    }

    protected function logSms($recipientId, $phone, $message, $status, $apiResponse, $errorMessage, $eventName = null, $sentByUserId = null): array
    {
        try {
            SmsLog::create([
                'recipient_user_id' => $recipientId,
                'phone_number' => $phone ?? 'N/A',
                'message' => $message,
                'status' => $status,
                'api_response' => $apiResponse ? json_encode($apiResponse, JSON_UNESCAPED_UNICODE) : null,
                'error_message' => $errorMessage,
                'sent_by_user_id' => $sentByUserId ?? Auth::id(),
                'event_name' => $eventName,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to log SMS to DB: '.$e->getMessage());
        }

        return [
            'ok' => $status === 'sent',
            'status' => $status,
            'phone' => $phone,
            'reason' => $errorMessage,
            'response' => $apiResponse,
        ];
    }
}
