<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AutomationRuleController;
use App\Http\Controllers\Api\BillController;
use App\Http\Controllers\Api\BnplPurchaseController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ConnectedAccountController;
use App\Http\Controllers\Api\CreditCardController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DebtController;
use App\Http\Controllers\Api\PennyChallengeController;
use App\Http\Controllers\Api\RuleTriggerController;
use App\Http\Controllers\Api\SavingsAccountController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\VoiceTranscriptionController;
use App\Http\Controllers\BankWebhookController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('api.dashboard');

    Route::get('/transactions', [TransactionController::class, 'index'])->name('api.transactions.index');
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])->name('api.transactions.show');
    Route::post('/transactions', [TransactionController::class, 'store'])->name('api.transactions.store');
    Route::post('/transactions/parse', [TransactionController::class, 'parse'])->name('api.transactions.parse');

    Route::get('/bills', [BillController::class, 'index'])->name('api.bills.index');
    Route::get('/bills/{bill}', [BillController::class, 'show'])->name('api.bills.show');

    Route::get('/categories', [CategoryController::class, 'index'])->name('api.categories.index');

    Route::get('/credit-cards', [CreditCardController::class, 'index'])->name('api.credit-cards.index');
    Route::get('/credit-cards/{creditCard}', [CreditCardController::class, 'show'])->name('api.credit-cards.show');

    Route::get('/debts', [DebtController::class, 'index'])->name('api.debts.index');
    Route::get('/debts/{debt}', [DebtController::class, 'show'])->name('api.debts.show');

    Route::get('/bnpl', [BnplPurchaseController::class, 'index'])->name('api.bnpl.index');
    Route::get('/bnpl/{bnplPurchase}', [BnplPurchaseController::class, 'show'])->name('api.bnpl.show');

    Route::get('/savings', [SavingsAccountController::class, 'index'])->name('api.savings.index');
    Route::get('/savings/{savingsAccount}', [SavingsAccountController::class, 'show'])->name('api.savings.show');

    Route::get('/penny-challenges', [PennyChallengeController::class, 'index'])->name('api.penny-challenges.index');
    Route::get('/penny-challenges/{pennyChallenge}', [PennyChallengeController::class, 'show'])->name('api.penny-challenges.show');

    Route::get('/accounts', [ConnectedAccountController::class, 'index'])->name('api.accounts.index');
    Route::get('/accounts/{connectedAccount}', [ConnectedAccountController::class, 'show'])->name('api.accounts.show');

    Route::get('/automation-rules', [AutomationRuleController::class, 'index'])->name('api.automation-rules.index');
    Route::get('/automation-rules/{automationRule}', [AutomationRuleController::class, 'show'])->name('api.automation-rules.show');

    Route::post('/rules/trigger', [RuleTriggerController::class, 'trigger'])->name('api.rules.trigger');
    Route::post('/rules/{ruleId}/trigger', [RuleTriggerController::class, 'trigger'])->name('api.rules.trigger.specific');

    Route::post('/voice/transcribe', [VoiceTranscriptionController::class, 'transcribe'])->name('api.voice.transcribe');
});

Route::post('/webhooks/monzo/{account}/{token}', [BankWebhookController::class, 'monzo'])->name('webhooks.monzo');
Route::post('/webhooks/starling/{account}', [BankWebhookController::class, 'starling'])->name('webhooks.starling');
