<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\CreditCard\CreateCreditCardAction;
use App\Actions\CreditCard\DeleteCreditCardAction;
use App\Actions\CreditCard\MakePaymentAction;
use App\Actions\CreditCard\RecordSpendingAction;
use App\Actions\CreditCard\UpdateCreditCardAction;
use App\DataTransferObjects\CreditCardDto;
use App\DataTransferObjects\CreditCardPaymentDto;
use App\DataTransferObjects\TransactionDto;
use App\Http\Controllers\Controller;
use App\Models\CreditCard;
use App\Queries\CreditCardQueries;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CreditCardController extends Controller
{
    public function index(Request $request, CreditCardQueries $queries): JsonResponse
    {
        $cards = $queries->allForUser($request->user());

        return response()->json(CreditCardDto::collect($cards));
    }

    public function show(Request $request, CreditCard $creditCard, CreditCardQueries $queries): JsonResponse
    {
        throw_unless($creditCard->user_id === $request->user()->id, AuthorizationException::class);

        $queries->withSpending($creditCard);

        return response()->json(CreditCardDto::fromModel($creditCard));
    }

    public function store(Request $request, CreateCreditCardAction $action): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'starting_balance' => 'numeric|min:0',
            'credit_limit' => 'nullable|numeric|min:0',
            'minimum_payment' => 'nullable|numeric|min:0',
            'interest_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $card = $action->handle([
            'user_id' => $request->user()->id,
            ...$validated,
        ]);

        $card->load(['payments', 'spending']);

        return response()->json(CreditCardDto::fromModel($card), 201);
    }

    public function update(Request $request, CreditCard $creditCard, UpdateCreditCardAction $action): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'starting_balance' => 'sometimes|numeric|min:0',
            'credit_limit' => 'nullable|numeric|min:0',
            'minimum_payment' => 'nullable|numeric|min:0',
            'interest_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $card = $action->handle($creditCard, $validated);
        $card->load(['payments', 'spending']);

        return response()->json(CreditCardDto::fromModel($card));
    }

    public function destroy(CreditCard $creditCard, DeleteCreditCardAction $action): JsonResponse
    {
        $action->handle($creditCard);

        return response()->json(['message' => 'Credit card deleted']);
    }

    public function payment(CreditCard $creditCard, MakePaymentAction $action, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'date' => 'nullable|date',
            'notes' => 'nullable|string|max:255',
        ]);

        $payment = $action->handle(
            $creditCard,
            (float) $validated['amount'],
            Carbon::parse($validated['date'] ?? now()),
            $validated['notes'] ?? null,
        );

        return response()->json(CreditCardPaymentDto::fromModel($payment), 201);
    }

    public function spending(CreditCard $creditCard, RecordSpendingAction $action, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'nullable|date',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        $transaction = $action->handle(
            $creditCard,
            (float) $validated['amount'],
            $validated['name'],
            Carbon::parse($validated['date'] ?? now()),
            $validated['category_id'] ?? null,
        );

        return response()->json(TransactionDto::fromModel($transaction), 201);
    }
}
