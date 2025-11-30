<?php

declare(strict_types=1);

use App\Http\Controllers\Api\TransactionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::post('/transactions/parse', [TransactionController::class, 'parse']);
});
