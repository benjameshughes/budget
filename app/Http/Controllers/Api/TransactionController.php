<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Transaction\CreateTransactionAction;
use App\Contracts\ExpenseParserInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ParseTransactionRequest;
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

    /**
     * Parse natural language text and create a transaction.
     */
    public function parse(ParseTransactionRequest $request, ExpenseParserInterface $parser, CreateTransactionAction $action): JsonResponse
    {
        $user = $request->user();

        // Parse the raw text
        $parsed = $parser->parse($request->validated('text'), $user->id);

        // Create the transaction using parsed data
        $transaction = $action->handle([
            'user_id' => $user->id,
            'name' => $parsed->name,
            'amount' => $parsed->amount,
            'type' => $parsed->type,
            'payment_date' => $parsed->date,
            'category_id' => $parsed->categoryId,
            'credit_card_id' => $parsed->creditCardId,
            'description' => "Parsed from: {$parsed->rawInput}",
        ]);

        return response()->json([
            'message' => 'Transaction created successfully',
            'parsed' => [
                'confidence' => $parsed->confidence,
                'detected_name' => $parsed->name,
                'detected_amount' => $parsed->amount,
                'detected_type' => $parsed->type,
                'detected_category' => $parsed->categoryName,
            ],
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
