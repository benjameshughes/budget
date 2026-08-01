<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Savings\CreateSavingsAccountAction;
use App\Actions\Savings\DeleteSavingsAccountAction;
use App\Actions\Savings\DepositAction;
use App\Actions\Savings\UpdateSavingsAccountAction;
use App\Actions\Savings\WithdrawAction;
use App\Concerns\PaginatesApiResponse;
use App\DataTransferObjects\SavingsAccountDto;
use App\DataTransferObjects\SavingsTransferDto;
use App\Http\Controllers\Controller;
use App\Models\SavingsAccount;
use App\Queries\SavingsQueries;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SavingsAccountController extends Controller
{
    use PaginatesApiResponse;

    public function index(Request $request, SavingsQueries $queries): JsonResponse
    {
        return $this->paginatedResponse(
            $queries->paginatedForUser($request->user()),
            SavingsAccountDto::class,
        );
    }

    public function show(Request $request, SavingsAccount $savingsAccount): JsonResponse
    {
        throw_unless($savingsAccount->user_id === $request->user()->id, AuthorizationException::class);

        $savingsAccount->load('transfers');

        return response()->json(SavingsAccountDto::fromModel($savingsAccount));
    }

    public function store(Request $request, CreateSavingsAccountAction $action): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'target_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $account = $action->handle([
            'user_id' => $request->user()->id,
            ...$validated,
        ]);

        $account->load('transfers');

        return response()->json(SavingsAccountDto::fromModel($account), 201);
    }

    public function update(Request $request, SavingsAccount $savingsAccount, UpdateSavingsAccountAction $action): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'target_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $action->handle(
            account: $savingsAccount,
            name: $validated['name'],
            targetAmount: isset($validated['target_amount']) ? (float) $validated['target_amount'] : null,
            notes: $validated['notes'] ?? null,
        );

        $savingsAccount->refresh()->load('transfers');

        return response()->json(SavingsAccountDto::fromModel($savingsAccount));
    }

    public function destroy(SavingsAccount $savingsAccount, DeleteSavingsAccountAction $action): JsonResponse
    {
        $action->handle($savingsAccount);

        return response()->json(['message' => 'Savings account deleted']);
    }

    public function deposit(Request $request, SavingsAccount $savingsAccount, DepositAction $action): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'date' => 'nullable|date',
            'notes' => 'nullable|string|max:255',
        ]);

        $transfer = $action->handle(
            $savingsAccount,
            (float) $validated['amount'],
            Carbon::parse($validated['date'] ?? now()),
            $validated['notes'] ?? null,
        );

        return response()->json(SavingsTransferDto::fromModel($transfer), 201);
    }

    public function withdraw(Request $request, SavingsAccount $savingsAccount, WithdrawAction $action): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'date' => 'nullable|date',
            'notes' => 'nullable|string|max:255',
        ]);

        $transfer = $action->handle(
            $savingsAccount,
            (float) $validated['amount'],
            Carbon::parse($validated['date'] ?? now()),
            $validated['notes'] ?? null,
        );

        return response()->json(SavingsTransferDto::fromModel($transfer), 201);
    }
}
