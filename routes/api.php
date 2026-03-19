<?php

declare(strict_types=1);

use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\VoiceTranscriptionController;
use App\Http\Controllers\BankWebhookController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::post('/transactions/parse', [TransactionController::class, 'parse']);
    Route::post('/voice/transcribe', [VoiceTranscriptionController::class, 'transcribe']);
});

// Bank webhooks — no auth middleware, verified via HMAC/token
Route::post('/webhooks/monzo/{account}/{token}', [BankWebhookController::class, 'monzo'])->name('webhooks.monzo');
Route::post('/webhooks/starling/{account}', [BankWebhookController::class, 'starling'])->name('webhooks.starling');
