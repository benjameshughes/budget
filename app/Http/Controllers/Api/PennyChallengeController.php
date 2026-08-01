<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\DataTransferObjects\PennyChallengeDto;
use App\Http\Controllers\Controller;
use App\Models\PennyChallenge;
use App\Queries\PennyChallengeQueries;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PennyChallengeController extends Controller
{
    public function index(Request $request, PennyChallengeQueries $queries): JsonResponse
    {
        $challenges = $queries->allForUser($request->user());

        return response()->json(PennyChallengeDto::collect($challenges));
    }

    public function show(Request $request, PennyChallenge $pennyChallenge): JsonResponse
    {
        throw_unless($pennyChallenge->user_id === $request->user()->id, AuthorizationException::class);

        $pennyChallenge->load(['days', 'depositedDays', 'pendingDays']);

        return response()->json(PennyChallengeDto::fromModel($pennyChallenge));
    }
}
