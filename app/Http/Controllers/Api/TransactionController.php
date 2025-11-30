<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Transaction\CreateTransactionAction;
use App\Contracts\ExpenseParserInterface;
use App\DataTransferObjects\Actions\CreateTransactionData;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ParseTransactionRequest;
use App\Http\Requests\Api\StoreTransactionRequest;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

final class TransactionController extends Controller
{
    /**
     * Store a newly created transaction.
     */
    public function store(StoreTransactionRequest $request, CreateTransactionAction $action): JsonResponse
    {
        $data = new CreateTransactionData(
            userId: $request->user()->id,
            name: $request->validated('name'),
            amount: (float) $request->validated('amount'),
            type: TransactionType::from($request->validated('type')),
            paymentDate: $request->validated('date') ? Carbon::parse($request->validated('date')) : Carbon::now(),
            categoryId: $request->validated('category_id'),
            description: $request->validated('description'),
        );

        $transaction = $action->handle($data);
        $transaction->refresh();

        $response = [
            'message' => 'Transaction created successfully',
            'transaction' => [
                'id' => $transaction->id,
                'name' => $transaction->name,
                'amount' => $transaction->amount,
                'type' => $transaction->type->value,
                'date' => $transaction->payment_date->toDateString(),
            ],
        ];

        // Include AI feedback if available
        if ($transaction->feedback && $transaction->feedback->feedback) {
            $response['feedback'] = $transaction->feedback->feedback;
        }

        return response()->json($response, 201);
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
        $data = new CreateTransactionData(
            userId: $user->id,
            name: $parsed->name,
            amount: $parsed->amount,
            type: TransactionType::from($parsed->type),
            paymentDate: Carbon::parse($parsed->date),
            categoryId: $parsed->categoryId,
            creditCardId: $parsed->creditCardId,
            description: "Parsed from: {$parsed->rawInput}",
        );

        $transaction = $action->handle($data);
        $transaction->refresh();

        $response = [
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
        ];

        // Include AI feedback if available
        if ($transaction->feedback && $transaction->feedback->feedback) {
            $response['feedback'] = $transaction->feedback->feedback;
        }

        return response()->json($response, 201);
    }
}
