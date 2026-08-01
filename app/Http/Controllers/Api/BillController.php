<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Bill\CreateBillAction;
use App\Actions\Bill\DeleteBillAction;
use App\Actions\Bill\MarkBillPaidAction;
use App\Actions\Bill\ToggleBillActiveAction;
use App\Actions\Bill\UpdateBillAction;
use App\DataTransferObjects\Actions\CreateBillData;
use App\DataTransferObjects\Actions\UpdateBillData;
use App\DataTransferObjects\BillDto;
use App\DataTransferObjects\TransactionDto;
use App\Enums\BillCadence;
use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Queries\BillQueries;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BillController extends Controller
{
    public function index(Request $request, BillQueries $queries): JsonResponse
    {
        $bills = $queries->allForUser($request->user());

        return response()->json(BillDto::collect($bills));
    }

    public function show(Request $request, Bill $bill): JsonResponse
    {
        throw_unless($bill->user_id === $request->user()->id, AuthorizationException::class);

        $bill->load('category');

        return response()->json(BillDto::fromModel($bill));
    }

    public function store(Request $request, CreateBillAction $action): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'cadence' => 'required|string|in:'.implode(',', array_column(BillCadence::cases(), 'value')),
            'next_due_date' => 'required|date',
            'category_id' => 'nullable|exists:categories,id',
            'interval_every' => 'nullable|integer|min:1',
            'end_date' => 'nullable|date|after:next_due_date',
            'autopay' => 'nullable|boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        $bill = $action->handle(new CreateBillData(
            userId: $request->user()->id,
            name: $validated['name'],
            amount: (float) $validated['amount'],
            cadence: BillCadence::from($validated['cadence']),
            nextDueDate: Carbon::parse($validated['next_due_date']),
            categoryId: $validated['category_id'] ?? null,
            intervalEvery: $validated['interval_every'] ?? 1,
            endDate: isset($validated['end_date']) ? Carbon::parse($validated['end_date']) : null,
            autopay: $validated['autopay'] ?? false,
            notes: $validated['notes'] ?? null,
        ));

        $bill->load('category');

        return response()->json(BillDto::fromModel($bill), 201);
    }

    public function update(Request $request, Bill $bill, UpdateBillAction $action): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'cadence' => 'required|string|in:'.implode(',', array_column(BillCadence::cases(), 'value')),
            'next_due_date' => 'required|date',
            'category_id' => 'nullable|exists:categories,id',
            'interval_every' => 'nullable|integer|min:1',
            'end_date' => 'nullable|date',
            'autopay' => 'nullable|boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        $bill = $action->handle($bill, new UpdateBillData(
            name: $validated['name'],
            amount: (float) $validated['amount'],
            cadence: BillCadence::from($validated['cadence']),
            nextDueDate: Carbon::parse($validated['next_due_date']),
            categoryId: $validated['category_id'] ?? null,
            intervalEvery: $validated['interval_every'] ?? 1,
            endDate: isset($validated['end_date']) ? Carbon::parse($validated['end_date']) : null,
            autopay: $validated['autopay'] ?? false,
            notes: $validated['notes'] ?? null,
        ));

        $bill->load('category');

        return response()->json(BillDto::fromModel($bill));
    }

    public function destroy(Bill $bill, DeleteBillAction $action): JsonResponse
    {
        $action->handle($bill);

        return response()->json(['message' => 'Bill deleted']);
    }

    public function markPaid(Request $request, Bill $bill, MarkBillPaidAction $action): JsonResponse
    {
        $validated = $request->validate([
            'paid_date' => 'nullable|date',
            'notes' => 'nullable|string|max:255',
        ]);

        $transaction = $action->handle(
            $bill,
            Carbon::parse($validated['paid_date'] ?? now()),
            $validated['notes'] ?? null,
        );

        $transaction->load('category');

        return response()->json(TransactionDto::fromModel($transaction), 201);
    }

    public function toggleActive(Bill $bill, ToggleBillActiveAction $action): JsonResponse
    {
        $bill = $action->handle($bill);
        $bill->load('category');

        return response()->json(BillDto::fromModel($bill));
    }
}
