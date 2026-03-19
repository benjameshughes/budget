<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\ProcessBankWebhookJob;
use App\Models\ConnectedAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class BankWebhookController extends Controller
{
    /**
     * Handle incoming Monzo webhook.
     *
     * Monzo uses URL-embedded token verification (no HMAC).
     * URL format: /api/webhooks/monzo/{account}/{token}
     */
    public function monzo(Request $request, ConnectedAccount $account, string $token): JsonResponse
    {
        // Verify the URL-embedded token matches the stored webhook secret
        if (! hash_equals($account->webhook_secret ?? '', $token)) {
            Log::warning('Monzo webhook: invalid token', [
                'account_id' => $account->id,
            ]);

            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $payload = $request->all();

        // Respond quickly, process async
        ProcessBankWebhookJob::dispatch($account, $payload);

        return response()->json(['status' => 'ok']);
    }

    /**
     * Handle incoming Starling webhook.
     *
     * Starling uses HMAC-SHA512 signature verification.
     * The X-Hook-Signature header contains the HMAC of the request body.
     */
    public function starling(Request $request, ConnectedAccount $account): JsonResponse
    {
        $signature = $request->header('X-Hook-Signature', '');
        $payload = $request->getContent();

        $expectedSignature = hash_hmac('sha512', $payload, $account->webhook_secret ?? '');

        if (! hash_equals($expectedSignature, $signature)) {
            Log::warning('Starling webhook: invalid signature', [
                'account_id' => $account->id,
            ]);

            return response()->json(['error' => 'Unauthorized'], 403);
        }

        ProcessBankWebhookJob::dispatch($account, $request->all());

        return response()->json(['status' => 'ok']);
    }
}
