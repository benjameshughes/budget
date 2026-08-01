<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Debt\CreateDebtAction;
use App\Actions\Debt\DeleteDebtAction;
use App\Actions\Debt\RecordPaymentAction;
use App\Actions\Debt\UpdateDebtAction;
use App\Concerns\PaginatesApiResponse;
use App\DataTransferObjects\DebtDto;
use App\DataTransferObjects\DebtPaymentDto;
use App\Http\Controllers\Controller;
use App\Models\Debt;
use App\Queries\DebtQueries;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DebtController extends Controller
{
    use PaginatesApiResponse;

    public function index(Request $request, DebtQueries $queries): JsonResponse
    {
        return $this->paginatedResponse(
            $queries->paginatedForUser($request->user()),
            DebtDto::class,
        );
    }

    public function show(Request $request, Debt $debt): JsonResponse
    {
        throw_unless($debt->user_id === $request->user()->id, AuthorizationException::class);

        $debt->load('payments');

        return response()->json(DebtDto::fromModel($debt));
    }

    public function store(Request $request, CreateDebtAction $action): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'starting_balance' => 'required|numeric|min:0.01',
            'minimum_payment' => 'nullable|numeric|min:0',
            'interest_rate' => 'nullable|numeric|min:0|max:100',
            'due_day' => 'nullable|integer|min:1|max:31',
        ]);

        $debt = $action->handle([
            'user_id' => $request->user()->id,
            ...$validated,
        ]);

        $debt->load('payments');

        return response()->json(DebtDto::fromModel($debt), 201);
    }

    public function update(Request $request, Debt $debt, UpdateDebtAction $action): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'starting_balance' => 'sometimes|numeric|min:0',
            'minimum_payment' => 'nullable|numeric|min:0',
            'interest_rate' => 'nullable|numeric|min:0|max:100',
            'due_day' => 'nullable|integer|min:1|max:31',
        ]);

        $debt = $action->handle($debt, $validated);
        $debt->load('payments');

        return response()->json(DebtDto::fromModel($debt));
    }

    public function destroy(Debt $debt, DeleteDebtAction $action): JsonResponse
    {
        $action->handle($debt);

        return response()->json(['message' => 'Debt deleted']);
    }

    public function payment(Debt $debt, RecordPaymentAction $action, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'date' => 'nullable|date',
            'notes' => 'nullable|string|max:255',
        ]);

        $payment = $action->handle(
            $debt,
            (float) $validated['amount'],
            Carbon::parse($validated['date'] ?? now()),
            $validated['notes'] ?? null,
        );

        return response()->json(DebtPaymentDto::fromModel($payment), 201);
    }
}
