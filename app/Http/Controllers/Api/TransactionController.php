<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Transaction\CreateTransactionAction;
use App\Actions\Transaction\DeleteTransactionAction;
use App\Concerns\PaginatesApiResponse;
use App\Contracts\ExpenseParserInterface;
use App\DataTransferObjects\Actions\CreateTransactionData;
use App\DataTransferObjects\TransactionDto;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ParseTransactionRequest;
use App\Http\Requests\Api\StoreTransactionRequest;
use App\Models\Transaction;
use App\Queries\TransactionQueries;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TransactionController extends Controller
{
    use PaginatesApiResponse;

    public function index(Request $request, TransactionQueries $queries): JsonResponse
    {
        return $this->paginatedResponse(
            $queries->paginated($request->user()),
            TransactionDto::class,
        );
    }

    public function show(Request $request, Transaction $transaction): JsonResponse
    {
        throw_unless($transaction->user_id === $request->user()->id, AuthorizationException::class);

        $transaction->load(['category', 'creditCard', 'feedback']);

        return response()->json(TransactionDto::fromModel($transaction));
    }

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
        $transaction->load(['category', 'feedback']);

        return response()->json([
            'message' => 'Transaction created successfully',
            'transaction' => TransactionDto::fromModel($transaction),
        ], 201);
    }

    public function parse(ParseTransactionRequest $request, ExpenseParserInterface $parser, CreateTransactionAction $action): JsonResponse
    {
        $user = $request->user();

        $parsed = $parser->parse($request->validated('text'), $user->id);

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
        $transaction->load(['category', 'feedback']);

        return response()->json([
            'message' => 'Transaction created successfully',
            'parsed' => [
                'confidence' => $parsed->confidence,
                'detected_name' => $parsed->name,
                'detected_amount' => $parsed->amount,
                'detected_type' => $parsed->type,
                'detected_category' => $parsed->categoryName,
            ],
            'transaction' => TransactionDto::fromModel($transaction),
        ], 201);
    }

    public function destroy(Transaction $transaction, DeleteTransactionAction $action): JsonResponse
    {
        $action->handle($transaction);

        return response()->json(['message' => 'Transaction deleted']);
    }
}
