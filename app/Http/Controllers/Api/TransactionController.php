<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Transaction\CreateTransactionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreTransactionRequest;
use Illuminate\Http\JsonResponse;

final class TransactionController extends Controller
{
    /**
     * Store a newly created transaction.
     */
    public function store(StoreTransactionRequest $request, CreateTransactionAction $action): JsonResponse
    {
        $transaction = $action->handle([
            'user_id' => $request->user()->id,
            'name' => $request->validated('name'),
            'amount' => $request->validated('amount'),
            'type' => $request->validated('type'),
            'payment_date' => $request->validated('date') ?? now(),
            'category_id' => $request->validated('category_id'),
            'description' => $request->validated('description'),
        ]);

        return response()->json([
            'message' => 'Transaction created successfully',
            'transaction' => [
                'id' => $transaction->id,
                'name' => $transaction->name,
                'amount' => $transaction->amount,
                'type' => $transaction->type->value,
                'date' => $transaction->payment_date->toDateString(),
            ],
        ], 201);
    }
}
