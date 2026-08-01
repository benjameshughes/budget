<?php

declare(strict_types=1);

use App\Http\Controllers\Api\BillController;
use App\Http\Controllers\Api\BnplPurchaseController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CreditCardController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DebtController;
use App\Http\Controllers\Api\PennyChallengeController;
use App\Http\Controllers\Api\SavingsAccountController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\VoiceTranscriptionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::middleware('throttle:api-read')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('api.dashboard');

        Route::get('/transactions', [TransactionController::class, 'index'])->name('api.transactions.index');
        Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])->name('api.transactions.show');

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
    });

    Route::middleware('throttle:api-write')->group(function () {
        Route::post('/transactions', [TransactionController::class, 'store'])->name('api.transactions.store');
        Route::post('/transactions/parse', [TransactionController::class, 'parse'])->name('api.transactions.parse');
        Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy'])->name('api.transactions.destroy');

        Route::post('/bills', [BillController::class, 'store'])->name('api.bills.store');
        Route::put('/bills/{bill}', [BillController::class, 'update'])->name('api.bills.update');
        Route::delete('/bills/{bill}', [BillController::class, 'destroy'])->name('api.bills.destroy');
        Route::post('/bills/{bill}/paid', [BillController::class, 'markPaid'])->name('api.bills.paid');
        Route::post('/bills/{bill}/toggle', [BillController::class, 'toggleActive'])->name('api.bills.toggle');

        Route::post('/credit-cards', [CreditCardController::class, 'store'])->name('api.credit-cards.store');
        Route::put('/credit-cards/{creditCard}', [CreditCardController::class, 'update'])->name('api.credit-cards.update');
        Route::delete('/credit-cards/{creditCard}', [CreditCardController::class, 'destroy'])->name('api.credit-cards.destroy');
        Route::post('/credit-cards/{creditCard}/payments', [CreditCardController::class, 'payment'])->name('api.credit-cards.payment');
        Route::post('/credit-cards/{creditCard}/spending', [CreditCardController::class, 'spending'])->name('api.credit-cards.spending');

        Route::post('/debts', [DebtController::class, 'store'])->name('api.debts.store');
        Route::put('/debts/{debt}', [DebtController::class, 'update'])->name('api.debts.update');
        Route::delete('/debts/{debt}', [DebtController::class, 'destroy'])->name('api.debts.destroy');
        Route::post('/debts/{debt}/payments', [DebtController::class, 'payment'])->name('api.debts.payment');

        Route::post('/bnpl', [BnplPurchaseController::class, 'store'])->name('api.bnpl.store');
        Route::delete('/bnpl/{bnplPurchase}', [BnplPurchaseController::class, 'destroy'])->name('api.bnpl.destroy');
        Route::post('/bnpl/installments/{installment}/paid', [BnplPurchaseController::class, 'markPaid'])->name('api.bnpl.installment.paid');

        Route::post('/savings', [SavingsAccountController::class, 'store'])->name('api.savings.store');
        Route::put('/savings/{savingsAccount}', [SavingsAccountController::class, 'update'])->name('api.savings.update');
        Route::delete('/savings/{savingsAccount}', [SavingsAccountController::class, 'destroy'])->name('api.savings.destroy');
        Route::post('/savings/{savingsAccount}/deposit', [SavingsAccountController::class, 'deposit'])->name('api.savings.deposit');
        Route::post('/savings/{savingsAccount}/withdraw', [SavingsAccountController::class, 'withdraw'])->name('api.savings.withdraw');

        Route::post('/penny-challenges', [PennyChallengeController::class, 'store'])->name('api.penny-challenges.store');
        Route::post('/penny-challenges/{pennyChallenge}/deposit', [PennyChallengeController::class, 'markDeposited'])->name('api.penny-challenges.deposit');

        Route::post('/voice/transcribe', [VoiceTranscriptionController::class, 'transcribe'])->name('api.voice.transcribe');
    });
});
