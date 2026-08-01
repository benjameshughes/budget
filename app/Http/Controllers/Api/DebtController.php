<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\DataTransferObjects\DebtDto;
use App\Http\Controllers\Controller;
use App\Models\Debt;
use App\Queries\DebtQueries;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DebtController extends Controller
{
    public function index(Request $request, DebtQueries $queries): JsonResponse
    {
        $debts = $queries->allForUser($request->user());

        return response()->json(DebtDto::collect($debts));
    }

    public function show(Request $request, Debt $debt): JsonResponse
    {
        throw_unless($debt->user_id === $request->user()->id, AuthorizationException::class);

        $debt->load('payments');

        return response()->json(DebtDto::fromModel($debt));
    }
}
