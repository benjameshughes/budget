<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\DataTransferObjects\SavingsAccountDto;
use App\Http\Controllers\Controller;
use App\Models\SavingsAccount;
use App\Queries\SavingsQueries;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SavingsAccountController extends Controller
{
    public function index(Request $request, SavingsQueries $queries): JsonResponse
    {
        $accounts = $queries->allForUser($request->user());

        return response()->json(SavingsAccountDto::collect($accounts));
    }

    public function show(Request $request, SavingsAccount $savingsAccount): JsonResponse
    {
        throw_unless($savingsAccount->user_id === $request->user()->id, AuthorizationException::class);

        $savingsAccount->load('transfers');

        return response()->json(SavingsAccountDto::fromModel($savingsAccount));
    }
}
