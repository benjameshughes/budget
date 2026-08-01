<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Bnpl\CreatePurchaseAction;
use App\Actions\Bnpl\DeletePurchaseAction;
use App\Actions\Bnpl\MarkInstallmentPaidAction;
use App\Concerns\PaginatesApiResponse;
use App\DataTransferObjects\BnplInstallmentDto;
use App\DataTransferObjects\BnplPurchaseDto;
use App\Enums\BnplProvider;
use App\Http\Controllers\Controller;
use App\Models\BnplInstallment;
use App\Models\BnplPurchase;
use App\Queries\BnplQueries;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BnplPurchaseController extends Controller
{
    use PaginatesApiResponse;

    public function index(Request $request, BnplQueries $queries): JsonResponse
    {
        return $this->paginatedResponse(
            $queries->paginatedForUser($request->user()),
            BnplPurchaseDto::class,
        );
    }

    public function show(Request $request, BnplPurchase $bnplPurchase): JsonResponse
    {
        throw_unless($bnplPurchase->user_id === $request->user()->id, AuthorizationException::class);

        $bnplPurchase->load('installments');

        return response()->json(BnplPurchaseDto::fromModel($bnplPurchase));
    }

    public function store(Request $request, CreatePurchaseAction $action): JsonResponse
    {
        $validated = $request->validate([
            'merchant' => 'required|string|max:255',
            'total_amount' => 'required|numeric|min:0.01',
            'provider' => 'required|string|in:'.implode(',', array_column(BnplProvider::cases(), 'value')),
            'purchase_date' => 'nullable|date',
            'fee' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:255',
        ]);

        $purchase = $action->handle(
            user: $request->user(),
            merchant: $validated['merchant'],
            total: (float) $validated['total_amount'],
            provider: BnplProvider::from($validated['provider']),
            purchaseDate: Carbon::parse($validated['purchase_date'] ?? now()),
            fee: (float) ($validated['fee'] ?? 0),
            notes: $validated['notes'] ?? null,
        );

        return response()->json(BnplPurchaseDto::fromModel($purchase), 201);
    }

    public function destroy(BnplPurchase $bnplPurchase, DeletePurchaseAction $action): JsonResponse
    {
        $action->handle($bnplPurchase);

        return response()->json(['message' => 'BNPL purchase deleted']);
    }

    public function markPaid(Request $request, BnplInstallment $installment, MarkInstallmentPaidAction $action): JsonResponse
    {
        throw_unless($installment->user_id === $request->user()->id, AuthorizationException::class);

        $validated = $request->validate([
            'paid_date' => 'nullable|date',
        ]);

        $installment = $action->handle(
            $installment,
            isset($validated['paid_date']) ? Carbon::parse($validated['paid_date']) : null,
        );

        return response()->json(BnplInstallmentDto::fromModel($installment));
    }
}
