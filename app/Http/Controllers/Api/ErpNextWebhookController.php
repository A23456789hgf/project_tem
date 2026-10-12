<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessErpNextWebhookJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ErpNextWebhookController extends Controller
{
    /**
     * Handle incoming webhooks from ERPNext
     */
    public function handle(Request $request)
    {
        // 1. Webhook Secret & Signature Verification
        if (! $this->verifySignature($request)) {
            Log::warning('Unauthorized ERPNext Webhook Attempt', ['ip' => $request->ip()]);

            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $doctype = $request->input('doctype');
        $event = $request->input('event');
        $data = $request->input('data');

        if (! $doctype || ! $event || ! $data) {
            return response()->json(['error' => 'Invalid Payload'], 400);
        }

        // 2. Fast logging
        $recordId = $data['name'] ?? null;
        Log::info('Received ERPNext Webhook', ['doctype' => $doctype, 'event' => $event, 'record_id' => $recordId]);

        // 3. Dispatch to Queue
        ProcessErpNextWebhookJob::dispatch($doctype, $event, $data);

        return response()->json(['status' => 'queued']);
    }

    /**
     * Verify the HMAC signature sent by ERPNext
     */
    private function verifySignature(Request $request): bool
    {
        $secret = config('services.frappe.webhook_secret');
        if (empty($secret)) {
            // If no secret is configured, bypass for local testing or strict environment requirement
            return true;
        }

        $signature = $request->header('X-Frappe-Webhook-Signature');
        if (! $signature) {
            $signature = $request->header('X-Frappe-Signature');
        }
        if (! $signature) {
            // Check alternative headers if any
            $signature = $request->header('X-ERP-Signature');
        }

        if (! $signature) {
            return false;
        }

        $payload = $request->getContent();

        // Frappe computes HMAC SHA256 of raw data and then base64 encodes it.
        $expectedSignature = base64_encode(hash_hmac('sha256', $payload, $secret, true));

        return hash_equals($expectedSignature, $signature);
    }
}
