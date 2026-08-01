<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\PennyChallenge\CreatePennyChallengeAction;
use App\Actions\PennyChallenge\MarkDaysDepositedAction;
use App\Concerns\PaginatesApiResponse;
use App\DataTransferObjects\Actions\CreatePennyChallengeData;
use App\DataTransferObjects\PennyChallengeDto;
use App\DataTransferObjects\TransactionDto;
use App\Http\Controllers\Controller;
use App\Models\PennyChallenge;
use App\Queries\PennyChallengeQueries;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PennyChallengeController extends Controller
{
    use PaginatesApiResponse;

    public function index(Request $request, PennyChallengeQueries $queries): JsonResponse
    {
        return $this->paginatedResponse(
            $queries->paginatedForUser($request->user()),
            PennyChallengeDto::class,
        );
    }

    public function show(Request $request, PennyChallenge $pennyChallenge): JsonResponse
    {
        throw_unless($pennyChallenge->user_id === $request->user()->id, AuthorizationException::class);

        $pennyChallenge->load(['days', 'depositedDays', 'pendingDays']);

        return response()->json(PennyChallengeDto::fromModel($pennyChallenge));
    }

    public function store(Request $request, CreatePennyChallengeAction $action): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $challenge = $action->handle(new CreatePennyChallengeData(
            userId: $request->user()->id,
            name: $validated['name'],
            startDate: Carbon::parse($validated['start_date']),
            endDate: Carbon::parse($validated['end_date']),
        ));

        $challenge->load(['days', 'depositedDays', 'pendingDays']);

        return response()->json(PennyChallengeDto::fromModel($challenge), 201);
    }

    public function markDeposited(Request $request, PennyChallenge $pennyChallenge, MarkDaysDepositedAction $action): JsonResponse
    {
        throw_unless($pennyChallenge->user_id === $request->user()->id, AuthorizationException::class);

        $validated = $request->validate([
            'day_ids' => 'required|array|min:1',
            'day_ids.*' => 'integer|exists:penny_challenge_days,id',
        ]);

        $transaction = $action->handle($pennyChallenge, $validated['day_ids']);
        $transaction->load('category');

        return response()->json(TransactionDto::fromModel($transaction), 201);
    }
}
